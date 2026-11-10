import 'dart:io' show Platform;

import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../../features/auth/data/auth_service.dart';

/// Handler pesan saat app di background/terminated. Harus top-level.
/// Pesan bertipe `notification` otomatis ditampilkan sistem (lock screen).
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  debugPrint('FCM background: ${message.messageId}');
}

/// Push notification (Firebase Cloud Messaging) untuk lock screen.
class PushNotificationService {
  PushNotificationService._();
  static final PushNotificationService instance = PushNotificationService._();

  static const _channel = AndroidNotificationChannel(
    'soto_points',
    'Poin & Setoran Botol',
    description: 'Notifikasi saat botol berhasil disetor dan poin bertambah',
    importance: Importance.high,
  );

  final FlutterLocalNotificationsPlugin _local =
      FlutterLocalNotificationsPlugin();

  bool _available = false;
  String? _currentToken;

  /// Dipanggil sekali di main(). Aman jika Firebase belum dikonfigurasi.
  Future<void> init() async {
    if (kIsWeb) return;
    try {
      await Firebase.initializeApp();
      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

      await _local.initialize(
        settings: const InitializationSettings(
          android: AndroidInitializationSettings('@mipmap/ic_launcher'),
          iOS: DarwinInitializationSettings(),
        ),
      );
      await _local
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(_channel);

      // Saat app terbuka, FCM tidak menampilkan notifikasi otomatis -> tampilkan manual
      FirebaseMessaging.onMessage.listen(_showForeground);

      // Token diperbarui oleh Firebase -> kirim ulang ke server
      // ignore: deprecated_member_use
      FirebaseMessaging.instance.onTokenRefresh.listen((t) {
        _currentToken = t;
        _sendToken(t);
      });

      _available = true;
    } catch (e) {
      debugPrint(
          'Push notification nonaktif (Firebase belum dikonfigurasi): $e');
    }
  }

  /// Panggil setelah user login: minta izin & daftarkan token ke backend.
  Future<void> registerToken() async {
    if (!_available) return;
    try {
      final settings = await FirebaseMessaging.instance.requestPermission();
      if (settings.authorizationStatus == AuthorizationStatus.denied) return;

      // ignore: deprecated_member_use
      final token = await FirebaseMessaging.instance.getToken();
      if (token == null) return;
      _currentToken = token;
      await _sendToken(token);
    } catch (e) {
      debugPrint('Register FCM token error: $e');
    }
  }

  /// Panggil sebelum logout agar perangkat tidak menerima notifikasi user lama.
  Future<void> unregisterToken() async {
    final token = _currentToken;
    if (!_available || token == null) return;
    try {
      await AuthService().dio.delete('/fcm-token', data: {'token': token});
    } on DioException catch (e) {
      debugPrint('Unregister FCM token error: ${e.message}');
    }
    _currentToken = null;
  }

  Future<void> _sendToken(String token) async {
    try {
      await AuthService().dio.post('/fcm-token', data: {
        'token': token,
        'platform': Platform.isIOS ? 'ios' : 'android',
      });
    } on DioException catch (e) {
      debugPrint('Send FCM token error: ${e.message}');
    }
  }

  void _showForeground(RemoteMessage message) {
    final n = message.notification;
    if (n == null) return;
    _local.show(
      id: message.hashCode,
      title: n.title,
      body: n.body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          _channel.id,
          _channel.name,
          channelDescription: _channel.description,
          importance: Importance.high,
          priority: Priority.high,
          visibility: NotificationVisibility.public,
        ),
        iOS: const DarwinNotificationDetails(),
      ),
    );
  }
}
