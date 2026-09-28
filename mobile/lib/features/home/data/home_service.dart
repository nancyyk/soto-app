import 'package:dio/dio.dart';
import '../../auth/data/auth_service.dart';

class HomeService {
  final Dio _dio;

  HomeService() : _dio = AuthService().dio;

  Future<Map<String, dynamic>> getUserPoints(int userId) async {
    try {
      final response = await _dio.get('/users/$userId/points');
      return response.data as Map<String, dynamic>;
    } catch (e) {
      throw Exception('Gagal mengambil data poin: $e');
    }
  }
}
