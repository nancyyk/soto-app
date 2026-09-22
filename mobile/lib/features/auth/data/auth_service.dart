import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AuthService {
  // Android Emulator : http://10.0.2.2:8000/api/v1
  // iOS Simulator    : http://localhost:8000/api/v1
  // HP Fisik (LAN)   : http://192.168.x.x:8000/api/v1
  static const String baseUrl = 'http://localhost:8000/api/v1';

  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'auth_user';

  final Dio _dio;

  AuthService()
      : _dio = Dio(
          BaseOptions(
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

  // ---------- TOKEN ----------
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

  // ---------- USER ----------
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

  // ---------- REGISTER ----------
  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
  }) async {
    try {
      final response = await _dio.post(
        '$baseUrl/auth/register',
        data: {
          'nama': name,
          'email': email,
          'password': password,
        },
      );

      if (response.statusCode == 201) {
        final data = response.data as Map<String, dynamic>;
        final token = data['token'];
        final user = data['user'];

        if (token != null) await saveToken(token.toString());
        if (user != null) await saveUser(user as Map<String, dynamic>);

        return data;
      }
      throw Exception('Registrasi gagal (${response.statusCode})');
    } on DioException catch (e) {
      throw Exception(_parseError(e));
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // ---------- LOGIN ----------
  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _dio.post(
        '$baseUrl/auth/login',
        data: {
          'email': email,
          'password': password,
        },
      );

      if (response.statusCode == 200) {
        final data = response.data as Map<String, dynamic>;
        final token = data['token'];
        final user = data['user'];

        if (token != null) await saveToken(token.toString());
        if (user != null) await saveUser(user as Map<String, dynamic>);

        return data;
      }
      throw Exception('Login gagal (${response.statusCode})');
    } on DioException catch (e) {
      throw Exception(_parseError(e));
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // ---------- LOGOUT ----------
  Future<void> logout() async {
    try {
      final token = await getToken();
      if (token == null || token.isEmpty) return;

      await _dio.post('$baseUrl/auth/logout');
    } catch (e) {
      debugPrint('Logout API error: $e');
    } finally {
      await removeToken();
    }
  }

  // ---------- HELPER ----------
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
      if (data['message'] != null) {
        return data['message'].toString();
      }
    }
    return e.message ?? 'Terjadi kesalahan tidak diketahui.';
  }
}