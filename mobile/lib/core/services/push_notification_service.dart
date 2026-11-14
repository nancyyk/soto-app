import 'dart:io' show Platform;

import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../../features/auth/data/auth_service.dart';
import '../router/app_router.dart';

/// Handler pesan saat app di background/terminated.
/// HARUS top-level (bukan method class) dan diberi @pragma agar tidak di-tree-shake.
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  debugPrint('[FCM Background] messageId: ${message.messageId}, type: ${message.data['type']}');
}

/// Singleton service untuk Firebase Cloud Messaging (Android only).
class PushNotificationService {
  PushNotificationService._();
  static final PushNotificationService instance = PushNotificationService._();

  // ── Notification Channel Android ──────────────────────────────────────────
  static const String channelId = 'setoran_poin_channel';
  static const String channelName = 'Notifikasi Bank Sampah';
  static const String channelDescription =
      'Notifikasi setoran sampah, penukaran reward, dan peringatan tempat sampah penuh';

  static const AndroidNotificationChannel _androidChannel =
      AndroidNotificationChannel(
    channelId,
    channelName,
    description: channelDescription,
    importance: Importance.max,
    playSound: true,
    showBadge: true,
  );

  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  bool _initialized = false;
  String? _currentToken;

  VoidCallback? onForegroundNotification;

  // ── Inisialisasi ──────────────────────────────────────────────────────────

  Future<void> init() async {
    if (kIsWeb) return;
    if (!Platform.isAndroid) return;

    try {
      await Firebase.initializeApp();

      FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

      const initSettings = InitializationSettings(
        android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      );
      await _localNotifications.initialize(
        settings: initSettings,
        onDidReceiveNotificationResponse: _onLocalNotificationTapped,
      );

      // Mendaftarkan notification channel menggunakan konstanta _androidChannel
      await _localNotifications
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(_androidChannel);

      await FirebaseMessaging.instance
          .setForegroundNotificationPresentationOptions(
        alert: true,
        badge: true,
        sound: true,
      );

      FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

      // Menggunakan onTokenRefresh untuk mendapatkan FCM Registration Token resmi
      // yang kompatibel dengan FCM HTTP v1 REST API pada backend Laravel.
      // ignore: deprecated_member_use
      FirebaseMessaging.instance.onTokenRefresh.listen((newToken) {
        debugPrint('[FCM] Token diperbarui');
        _currentToken = newToken;
        _sendTokenToBackend(newToken);
      });

      _initialized = true;
      debugPrint('[FCM] Service berhasil diinisialisasi');
    } catch (e) {
      debugPrint('[FCM] Gagal inisialisasi: $e');
    }
  }

  Future<void> setupNavigationHandlers() async {
    if (!_initialized) return;

    FirebaseMessaging.onMessageOpenedApp.listen((message) {
      debugPrint('[FCM] onMessageOpenedApp: ${message.data}');
      _navigateFromMessage(message.data);
    });

    final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      debugPrint('[FCM] getInitialMessage: ${initialMessage.data}');
      await Future.delayed(const Duration(milliseconds: 500));
      _navigateFromMessage(initialMessage.data);
    }
  }

  // ── Token Management ──────────────────────────────────────────────────────

  /// Minta izin notifikasi dan daftarkan FCM Registration Token ke backend.
  /// Panggil setelah user berhasil LOGIN.
  Future<void> registerToken() async {
    if (!_initialized) return;
    try {
      final settings = await FirebaseMessaging.instance.requestPermission(
        alert: true,
        badge: true,
        sound: true,
        provisional: false,
      );

      if (settings.authorizationStatus == AuthorizationStatus.denied) {
        debugPrint('[FCM] Izin notifikasi ditolak user');
        return;
      }

      // Mengambil FCM registration token resmi untuk pengiriman push notification HTTP v1
      // ignore: deprecated_member_use
      final token = await FirebaseMessaging.instance.getToken();
      if (token == null || token.isEmpty) {
        debugPrint('[FCM] Token null/kosong, skip register');
        return;
      }

      _currentToken = token;
      debugPrint('[FCM] Token diperoleh: ${token.substring(0, 20)}...');
      await _sendTokenToBackend(token);
    } catch (e) {
      debugPrint('[FCM] registerToken error: $e');
    }
  }

  /// Hapus token dari backend. Panggil sebelum user LOGOUT.
  Future<void> unregisterToken() async {
    final token = _currentToken;
    if (!_initialized || token == null || token.isEmpty) return;
    try {
      await AuthService().dio.delete('/fcm-token', data: {'token': token});
      debugPrint('[FCM] Token dihapus dari backend');
    } on DioException catch (e) {
      debugPrint('[FCM] unregisterToken error: ${e.message}');
    } finally {
      _currentToken = null;
    }
  }

  // ── Private Helpers ───────────────────────────────────────────────────────

  Future<void> _sendTokenToBackend(String token) async {
    try {
      await AuthService().dio.post('/fcm-token', data: {
        'token': token,
        'platform': 'android',
      });
      debugPrint('[FCM] Token berhasil dikirim ke backend');
    } on DioException catch (e) {
      debugPrint('[FCM] Gagal kirim token ke backend: ${e.message}');
    }
  }

  void _handleForegroundMessage(RemoteMessage message) {
    debugPrint('[FCM Foreground] ${message.notification?.title}');

    final notification = message.notification;
    if (notification == null) return;

    _localNotifications.show(
      id: message.messageId.hashCode,
      title: notification.title,
      body: notification.body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          channelId,
          channelName,
          channelDescription: channelDescription,
          importance: Importance.max,
          priority: Priority.high,
          visibility: NotificationVisibility.public,
          playSound: true,
          icon: '@mipmap/ic_launcher',
        ),
      ),
      payload: message.data['type'],
    );

    onForegroundNotification?.call();
  }

  void _onLocalNotificationTapped(NotificationResponse response) {
    final type = response.payload;
    if (type == null) return;
    _navigateFromType(type);
  }

  void _navigateFromMessage(Map<String, dynamic> data) {
    final type = data['type']?.toString() ?? '';
    _navigateFromType(type);
  }

  void _navigateFromType(String type) {
    final router = AppRouter.router;
    switch (type) {
      case 'points_added':
      case 'setoran':
        router.go(AppRouter.history);
        break;
      case 'reward_success':
      case 'reward':
        router.go(AppRouter.rewardHistory);
        break;
      case 'machine_full':
      case 'sampah_penuh':
        router.go(AppRouter.notification);
        break;
      default:
        router.go(AppRouter.notification);
    }
  }
}
