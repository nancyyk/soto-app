import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/services/push_notification_service.dart';

class AuthService {
  // Android Emulator : http://10.0.2.2:8000/api/v1
  // HP Fisik (LAN)   : http://192.168.x.x:8000/api/v1
  static String get baseUrl {
    if (kIsWeb) return 'http://localhost:8000/api/v1';
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.windows) {
      return 'http://localhost:8000/api/v1';
    }
    // IP LAN / Wi-Fi host saat ini: 192.168.100.16
    // Jika menggunakan Android Emulator: ganti ke 10.0.2.2
    return 'http://192.168.30.98:8000/api/v1';
  }

  /// Base URL server tanpa prefix /api/v1 (untuk asset storage seperti gambar)
  static String get serverBaseUrl {
    return baseUrl.replaceAll(RegExp(r'/api/v1/?$'), '');
  }

  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'auth_user';

  final Dio _dio;

  // Dio client dengan interceptor token auth
  Dio get dio => _dio;

  AuthService()
      : _dio = Dio(
          BaseOptions(
            baseUrl: baseUrl,
            connectTimeout: const Duration(seconds: 15),
            receiveTimeout: const Duration(seconds: 15),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          ),
        ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await getToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (e, handler) {
          debugPrint('Dio Error: ${e.message}');
          handler.next(e);
        },
      ),
    );
  }

  // Token management
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  Future<void> removeToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
  }

  Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  // User cache
  Future<Map<String, dynamic>?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_userKey);
    if (raw == null) return null;
    try {
      return jsonDecode(raw) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  Future<void> saveUser(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user));
  }

  // Register
  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
  }) async {
    try {
      final response = await _dio.post(
        '/auth/register',
        data: {'nama': name, 'email': email, 'password': password},
      );

      if (response.statusCode == 201) {
        final data = response.data as Map<String, dynamic>;
        if (data['token'] != null) await saveToken(data['token'].toString());
        if (data['user'] != null) {
          await saveUser(data['user'] as Map<String, dynamic>);
        }
        return data;
      }
      throw Exception('Registrasi gagal (${response.statusCode})');
    } on DioException catch (e) {
      throw Exception(_parseError(e));
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // Login
  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _dio.post(
        '/auth/login',
        data: {'email': email, 'password': password},
      );

      if (response.statusCode == 200) {
        final data = response.data as Map<String, dynamic>;
        if (data['token'] != null) await saveToken(data['token'].toString());
        if (data['user'] != null) {
          await saveUser(data['user'] as Map<String, dynamic>);
        }
        return data;
      }
      throw Exception('Login gagal (${response.statusCode})');
    } on DioException catch (e) {
      throw Exception(_parseError(e));
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // Logout
  Future<void> logout() async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) return;
      await PushNotificationService.instance.unregisterToken();
      await _dio.post('/auth/logout');
    } catch (e) {
      debugPrint('Logout API error: $e');
    } finally {
      await removeToken();
    }
  }

  // Update local user
  Future<void> updateLocalUser(Map<String, dynamic> updatedFields) async {
    final current = await getUser() ?? {};
    current.addAll(updatedFields);
    await saveUser(current);
  }

  // Fetch profile
  Future<Map<String, dynamic>?> fetchProfile() async {
    try {
      final response = await _dio.get('/me');
      if (response.statusCode == 200 && response.data is Map) {
        final data = response.data as Map<String, dynamic>;
        if (data['user'] is Map) {
          final freshUser = Map<String, dynamic>.from(data['user'] as Map);
          await updateLocalUser(freshUser);
          return await getUser();
        }
      }
    } catch (e) {
      debugPrint('Fetch profile error: $e');
    }
    return await getUser();
  }

  // Update profile
  Future<Map<String, dynamic>> updateProfile({
    required String name,
  }) async {
    try {
      final response = await _dio.put(
        '/me',
        data: {'nama': name},
      );

      final data = response.data as Map<String, dynamic>;
      if (data['user'] != null) {
        await updateLocalUser(data['user'] as Map<String, dynamic>);
      }
      return data;
    } on DioException catch (e) {
      throw Exception(_parseError(e));
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // Change password
  Future<void> changePassword({
    required String oldPassword,
    required String newPassword,
  }) async {
    try {
      await _dio.put(
        '/me/password',
        data: {
          'old_password': oldPassword,
          'new_password': newPassword,
          'new_password_confirmation': newPassword,
        },
      );
    } on DioException catch (e) {
      throw Exception(_parseError(e));
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // Error parser helper
  String _parseError(DioException e) {
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout) {
      return 'Koneksi timeout. Periksa jaringan Anda.';
    }
    if (e.type == DioExceptionType.connectionError) {
      return 'Tidak dapat terhubung ke server.';
    }
    final data = e.response?.data;
    if (data is Map) {
      if (data['errors'] is Map) {
        final errors = data['errors'] as Map;
        final firstKey = errors.keys.first;
        final firstVal = errors[firstKey];
        if (firstVal is List && firstVal.isNotEmpty) {
          return firstVal.first.toString();
        }
      }
      if (data['message'] != null) return data['message'].toString();
    }
    return e.message ?? 'Terjadi kesalahan tidak diketahui.';
  }
}
