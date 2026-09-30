import 'package:dio/dio.dart';
import '../../auth/data/auth_service.dart';

class RewardService {
  final Dio _dio;

  RewardService() : _dio = AuthService().dio;

  Future<List<dynamic>> getRewards() async {
    try {
      final response = await _dio.get('/rewards');
      return response.data as List;
    } catch (e) {
      throw Exception('Gagal mengambil data reward: $e');
    }
  }

  Future<void> redeemReward(int rewardId, String address) async {
    try {
      await _dio.post('/rewards/redeem', data: {
        'reward_id': rewardId,
        'shipping_address': address,
      });
    } catch (e) {
      if (e is DioException && e.response != null) {
        throw Exception(e.response?.data['message'] ?? 'Gagal menukar reward');
      }
      throw Exception('Gagal menukar reward: $e');
    }
  }
}