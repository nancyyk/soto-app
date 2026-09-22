import 'package:dio/dio.dart';

class RfidService {
  final Dio dio;

  RfidService(this.dio);

  Future<void> startScan({String deviceId = 'esp32-soto-01'}) async {
    try {
      await dio.post('/api/v1/rfid/scan', data: {
        'device_id': deviceId,
      });
    } catch (e) {
      throw Exception('Gagal memulai scan. Pastikan Anda belum menghubungkan kartu.');
    }
  }

  Future<String> checkStatus() async {
    try {
      final response = await dio.get('/api/v1/rfid/status');
      return response.data['status'] ?? 'not_found';
    } catch (e) {
      return 'error';
    }
  }

  Future<void> unlinkCard() async {
    try {
      await dio.delete('/api/v1/rfid/unlink');
    } catch (e) {
      throw Exception('Gagal menghapus kartu.');
    }
  }
}