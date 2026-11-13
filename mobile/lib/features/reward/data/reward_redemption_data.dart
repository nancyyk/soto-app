import 'reward_data.dart';

class RewardRedemptionData {
  final int id;
  final int userId;
  final int rewardId;
  final int points;
  final String status;
  final String? shippingAddress;
  final String? recipientNumber;
  final String? expedition;
  final String? trackingNumber;
  final String? voucherCode;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final RewardData? reward;

  const RewardRedemptionData({
    required this.id,
    required this.userId,
    required this.rewardId,
    required this.points,
    required this.status,
    this.shippingAddress,
    this.recipientNumber,
    this.expedition,
    this.trackingNumber,
    this.voucherCode,
    this.createdAt,
    this.updatedAt,
    this.reward,
  });

  factory RewardRedemptionData.fromJson(Map<String, dynamic> json) {
    final rewardObj = json['reward'] != null && json['reward'] is Map<String, dynamic>
        ? RewardData.fromJson(json['reward'] as Map<String, dynamic>)
        : null;

    final rawVoucher = (json['kode_voucher'] ??
            json['voucher_code'] ??
            json['kode'])
        ?.toString();

    return RewardRedemptionData(
      id: json['id'] as int? ?? 0,
      userId: json['user_id'] as int? ?? 0,
      rewardId: json['reward_id'] as int? ?? 0,
      points: json['poin'] as int? ?? json['points_used'] as int? ?? 0,
      status: (json['status'] ?? 'Proses').toString(),
      shippingAddress: json['shipping_address'] as String?,
      recipientNumber: json['recipient_number'] as String?,
      expedition: json['ekspedisi'] as String?,
      trackingNumber: json['nomor_resi'] as String?,
      voucherCode: rawVoucher,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())?.toLocal()
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'].toString())?.toLocal()
          : null,
      reward: rewardObj,
    );
  }

  /// Mendapatkan kode voucher belanja (jika kategori Voucher)
  String? get effectiveVoucherCode {
    if (voucherCode != null && voucherCode!.trim().isNotEmpty) {
      return voucherCode!.trim();
    }
    if (reward?.category.toLowerCase() == 'voucher') {
      final codePrefix = reward?.code.isNotEmpty == true ? reward!.code : 'VCR';
      return 'SOTO-$codePrefix-${id.toString().padLeft(4, '0')}';
    }
    return null;
  }

  /// Status ternormalisasi (Proses, Pengiriman, Selesai)
  String get normalizedStatus {
    final s = status.trim().toLowerCase();
    if (s == 'selesai') return 'Selesai';
    if (s == 'pengiriman') return 'Pengiriman';
    return 'Proses';
  }

  /// Format tanggal ramah pengguna (contoh: 11 Okt 2026, 12:45 WIB)
  String get formattedDate {
    if (createdAt == null) return '-';
    final dt = createdAt!;
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'Mei',
      'Jun',
      'Jul',
      'Agu',
      'Sep',
      'Okt',
      'Nov',
      'Des'
    ];
    final hour = dt.hour.toString().padLeft(2, '0');
    final minute = dt.minute.toString().padLeft(2, '0');
    return '${dt.day} ${months[dt.month - 1]} ${dt.year}, $hour:$minute WIB';
  }
}
