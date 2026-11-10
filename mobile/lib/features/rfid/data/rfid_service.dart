import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

class RfidService {
  final Dio _dio;

  RfidService(this._dio);

  /// POST /rfid/scan (tanpa prefix /api/v1)
  Future<void> startScan() async {
    try {
      final res = await _dio.post('/rfid/scan');
      debugPrint('startScan OK: ${res.statusCode} ${res.data}');
    } on DioException catch (e) {
      debugPrint('startScan ERROR: ${e.response?.statusCode} ${e.response?.data}');
      throw Exception(_parseError(e));
    } catch (e) {
      throw Exception('Gagal memulai scan: $e');
    }
  }

  /// GET /rfid/status (tanpa prefix /api/v1) (mengembalikan map detail status & rfid_uid)
  Future<Map<String, dynamic>> checkStatusDetail() async {
    try {
      final res = await _dio.get('/rfid/status');
      if (res.data is Map) {
        return Map<String, dynamic>.from(res.data as Map);
      }
      return {'status': 'error'};
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError) {
        debugPrint('checkStatus network glitch: ${e.message}');
        return {'status': 'error'};
      }
      if (e.response?.statusCode == 401) {
        debugPrint('checkStatus 401: token invalid');
        return {'status': 'error'};
      }
      debugPrint('checkStatus ERROR: ${e.response?.statusCode} ${e.response?.data}');
      return {'status': 'error'};
    } catch (e) {
      debugPrint('checkStatus unexpected: $e');
      return {'status': 'error'};
    }
  }

  /// GET /rfid/status (tanpa prefix /api/v1)
  Future<String> checkStatus() async {
    final data = await checkStatusDetail();
    final status = data['status'];
    if (status is String) return status;
    return 'error';
  }

  /// DELETE /rfid/unlink (tanpa prefix /api/v1)
  Future<void> unlink() async {
    try {
      await _dio.delete('/rfid/unlink');
    } on DioException catch (e) {
      throw Exception(_parseError(e));
    } catch (e) {
      throw Exception('Gagal unlink kartu: $e');
    }
  }

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
    if (data is Map && data['message'] != null) {
      return data['message'].toString();
    }
    return e.message ?? 'Terjadi kesalahan tidak diketahui.';
  }
}