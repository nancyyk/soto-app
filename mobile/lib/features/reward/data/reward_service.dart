import 'package:dio/dio.dart';
import '../../auth/data/auth_service.dart';
import 'reward_redemption_data.dart';

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

  Future<RewardRedemptionData> redeemReward({
    required int rewardId,
    String? shippingAddress,
    String? recipientNumber,
  }) async {
    try {
      final Map<String, dynamic> body = {
        'reward_id': rewardId,
      };
      if (shippingAddress != null && shippingAddress.isNotEmpty) {
        body['shipping_address'] = shippingAddress;
      }
      if (recipientNumber != null && recipientNumber.isNotEmpty) {
        body['recipient_number'] = recipientNumber;
      }
      final response = await _dio.post('/rewards/redeem', data: body);
      final resData = response.data;
      if (resData is Map<String, dynamic> && resData['data'] != null) {
        return RewardRedemptionData.fromJson(
            resData['data'] as Map<String, dynamic>);
      }
      return RewardRedemptionData(
        id: 0,
        userId: 0,
        rewardId: rewardId,
        points: 0,
        status: 'Proses',
      );
    } catch (e) {
      if (e is DioException && e.response != null) {
        throw Exception(e.response?.data['message'] ?? 'Gagal menukar reward');
      }
      throw Exception('Gagal menukar reward: $e');
    }
  }

  Future<List<RewardRedemptionData>> getMyRedemptions() async {
    try {
      final response = await _dio.get('/rewards/my-redemptions');
      if (response.data is List) {
        return (response.data as List)
            .map((e) =>
                RewardRedemptionData.fromJson(e as Map<String, dynamic>))
            .toList();
      }
      return [];
    } catch (e) {
      if (e is DioException && e.response != null) {
        throw Exception(
            e.response?.data['message'] ?? 'Gagal mengambil riwayat reward');
      }
      throw Exception('Gagal mengambil riwayat reward: $e');
    }
  }
}
