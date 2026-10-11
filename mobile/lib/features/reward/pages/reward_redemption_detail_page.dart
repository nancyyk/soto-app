import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';

import '../data/reward_redemption_data.dart';
import '../widgets/reward_image.dart';

class RewardRedemptionDetailPage extends StatelessWidget {
  final RewardRedemptionData redemption;

  const RewardRedemptionDetailPage({
    super.key,
    required this.redemption,
  });

  static const Color green = Color(0xFF2D6A4F);
  static const Color lightGreen = Color(0xFFD8F3DC);
  static const Color textDark = Color(0xFF263238);

  @override
  Widget build(BuildContext context) {
    final reward = redemption.reward;
    final status = redemption.normalizedStatus;

    return Scaffold(
      backgroundColor: const Color(0xFFF9FAF9),
      body: SafeArea(
        child: Column(
          children: [
            // TOP BAR
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 12),
              child: Row(
                children: [
                  InkWell(
                    borderRadius: BorderRadius.circular(20),
                    onTap: () => context.pop(),
                    child: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        border: Border.all(color: const Color(0xFFE4E6E4)),
                      ),
                      child: const Icon(
                        Icons.arrow_back_ios_new,
                        size: 18,
                        color: textDark,
                      ),
                    ),
                  ),
                  const Expanded(
                    child: Center(
                      child: Text(
                        'Status Penukaran',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: textDark,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 36),
                ],
              ),
            ),

            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 30),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // CARD STATUS HEADER
                    _buildStatusOverview(context, status),

                    const SizedBox(height: 20),

                    // TRACKING TIMELINE / STEPPER
                    _buildTrackingTimeline(status),

                    const SizedBox(height: 20),

                    // REWARD DETAILS CARD
                    _buildRewardCard(reward),

                    const SizedBox(height: 20),

                    // DATA PENERIMA / PENGIRIMAN
                    _buildRecipientCard(context),

                    const SizedBox(height: 20),

                    // TRANSACTION SUMMARY
                    _buildSummaryCard(),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusOverview(BuildContext context, String status) {
    Color bg;
    Color fg;
    String statusDesc;
    IconData icon;

    switch (status.toLowerCase()) {
      case 'selesai':
        bg = const Color(0xFFDCFCE7);
        fg = const Color(0xFF166534);
        icon = Icons.check_circle_rounded;
        statusDesc = 'Reward Anda telah berhasil diselesaikan dan diterima.';
        break;
      case 'pengiriman':
        bg = const Color(0xFFDBEAFE);
        fg = const Color(0xFF1E40AF);
        icon = Icons.local_shipping_rounded;
        statusDesc = 'Reward sedang dalam perjalanan pengiriman ke alamat tujuan.';
        break;
      case 'proses':
      default:
        bg = const Color(0xFFFEF9C3);
        fg = const Color(0xFF854D0E);
        icon = Icons.hourglass_top_rounded;
        statusDesc = 'Admin sedang memverifikasi dan menyiapkan reward Anda.';
        break;
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: fg.withValues(alpha: 0.2)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: const BoxDecoration(
              color: Colors.white,
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: fg, size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Status: $status',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: fg,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  statusDesc,
                  style: TextStyle(
                    fontSize: 12,
                    color: fg.withValues(alpha: 0.85),
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTrackingTimeline(String status) {
    final step = _getStepIndex(status);

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE8EBE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Lacak Penukaran',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: textDark,
            ),
          ),
          const SizedBox(height: 16),
          _TimelineTile(
            title: 'Penukaran Diajukan',
            subtitle: redemption.formattedDate,
            isCompleted: true,
            isCurrent: step == 1,
            isFirst: true,
          ),
          _TimelineTile(
            title: 'Diproses Admin',
            subtitle: step >= 2
                ? 'Pengajuan telah dikonfirmasi dan diverifikasi admin'
                : 'Menunggu konfirmasi admin',
            isCompleted: step >= 2,
            isCurrent: step == 2,
          ),
          _TimelineTile(
            title: 'Pengiriman / Penyaluran',
            subtitle: step >= 3
                ? (redemption.trackingNumber != null
                    ? 'Ekspedisi: ${redemption.expedition ?? '-'} (${redemption.trackingNumber})'
                    : 'Reward sedang disalurkan/dikirimkan')
                : 'Menunggu proses pengiriman',
            isCompleted: step >= 3,
            isCurrent: step == 3,
          ),
          _TimelineTile(
            title: 'Selesai',
            subtitle: step >= 4
                ? 'Reward telah berhasil diterima'
                : 'Transaksi akan selesai setelah reward sampai',
            isCompleted: step >= 4,
            isCurrent: step == 4,
            isLast: true,
          ),
        ],
      ),
    );
  }

  int _getStepIndex(String status) {
    switch (status.toLowerCase()) {
      case 'selesai':
        return 4;
      case 'pengiriman':
        return 3;
      case 'proses':
      default:
        return 2;
    }
  }

  Widget _buildRewardCard(dynamic reward) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE8EBE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Rincian Reward',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: textDark,
            ),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Container(
                width: 70,
                height: 70,
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: const Color(0xFFF5F7F5),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFECECEC)),
                ),
                clipBehavior: Clip.antiAlias,
                child: RewardImage(
                  imagePath: reward?.imagePath ?? '',
                  fit: BoxFit.contain,
                  fallback: const Icon(
                    Icons.card_giftcard,
                    color: green,
                    size: 30,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (reward?.category != null)
                      Text(
                        reward!.category,
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: green,
                        ),
                      ),
                    const SizedBox(height: 2),
                    Text(
                      reward?.title ?? 'Reward #${redemption.rewardId}',
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: textDark,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      '${redemption.points} Poin',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFE53935),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildRecipientCard(BuildContext context) {
    final hasAddress = redemption.shippingAddress != null &&
        redemption.shippingAddress!.trim().isNotEmpty;
    final hasPhone = redemption.recipientNumber != null &&
        redemption.recipientNumber!.trim().isNotEmpty;
    final hasTracking = redemption.trackingNumber != null &&
        redemption.trackingNumber!.trim().isNotEmpty;

    if (!hasAddress && !hasPhone && !hasTracking) {
      return const SizedBox.shrink();
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE8EBE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Informasi Penerima & Pengiriman',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: textDark,
            ),
          ),
          const SizedBox(height: 12),

          // EKSPEDISI & RESI
          if (hasTracking) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF3F6FA),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE0E7F1)),
              ),
              child: Row(
                children: [
                  const Icon(
                    Icons.local_shipping_rounded,
                    color: Color(0xFF1E40AF),
                    size: 22,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          redemption.expedition ?? 'Kurir Ekspedisi',
                          style: const TextStyle(
                            fontSize: 11,
                            color: Color(0xFF6B7280),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          redemption.trackingNumber!,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            fontFamily: 'monospace',
                            color: Color(0xFF1E3A8A),
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.copy_rounded, size: 18),
                    color: const Color(0xFF1E40AF),
                    tooltip: 'Salin Resi',
                    onPressed: () {
                      Clipboard.setData(
                        ClipboardData(text: redemption.trackingNumber!),
                      );
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Nomor resi berhasil disalin!'),
                          duration: Duration(seconds: 2),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
          ],

          // NO HP / RECIPIENT NUMBER
          if (hasPhone) ...[
            _InfoRow(
              icon: Icons.phone_android_rounded,
              label: 'Nomor Penerima / Akun',
              value: redemption.recipientNumber!,
            ),
            const SizedBox(height: 10),
          ],

          // ALAMAT
          if (hasAddress)
            _InfoRow(
              icon: Icons.location_on_outlined,
              label: 'Alamat Pengiriman',
              value: redemption.shippingAddress!,
            ),
        ],
      ),
    );
  }

  Widget _buildSummaryCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE8EBE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Ringkasan Transaksi',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: textDark,
            ),
          ),
          const SizedBox(height: 12),
          _SummaryRow(label: 'ID Penukaran', value: '#${redemption.id}'),
          const SizedBox(height: 8),
          _SummaryRow(
            label: 'Waktu Transaksi',
            value: redemption.formattedDate,
          ),
          const SizedBox(height: 8),
          _SummaryRow(
            label: 'Total Poin Terpakai',
            value: '-${redemption.points} Poin',
            valueColor: const Color(0xFFE53935),
            isBold: true,
          ),
        ],
      ),
    );
  }
}

class _TimelineTile extends StatelessWidget {
  final String title;
  final String subtitle;
  final bool isCompleted;
  final bool isCurrent;
  final bool isFirst;
  final bool isLast;

  const _TimelineTile({
    required this.title,
    required this.subtitle,
    required this.isCompleted,
    this.isCurrent = false,
    this.isFirst = false,
    this.isLast = false,
  });

  @override
  Widget build(BuildContext context) {
    const activeColor = Color(0xFF2D6A4F);
    const inactiveColor = Color(0xFFD1D5DB);

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // STEPPER LINE & ICON
          SizedBox(
            width: 32,
            child: Column(
              children: [
                Container(
                  width: 22,
                  height: 22,
                  decoration: BoxDecoration(
                    color: isCompleted
                        ? activeColor
                        : Colors.white,
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: isCompleted ? activeColor : inactiveColor,
                      width: 2,
                    ),
                  ),
                  child: Center(
                    child: isCompleted
                        ? const Icon(
                            Icons.check,
                            size: 13,
                            color: Colors.white,
                          )
                        : Container(
                            width: 6,
                            height: 6,
                            decoration: BoxDecoration(
                              color: inactiveColor,
                              shape: BoxShape.circle,
                            ),
                          ),
                  ),
                ),
                if (!isLast)
                  Expanded(
                    child: Container(
                      width: 2,
                      color: isCompleted ? activeColor : inactiveColor,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(bottom: isLast ? 0 : 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight:
                          isCurrent || isCompleted ? FontWeight.w700 : FontWeight.w500,
                      color: isCompleted
                          ? RewardRedemptionDetailPage.textDark
                          : const Color(0xFF9CA3AF),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 11,
                      color: const Color(0xFF6B7280),
                      height: 1.3,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: const Color(0xFF2D6A4F)),
        const SizedBox(width: 8),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 11,
                  color: Color(0xFF6B7280),
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: RewardRedemptionDetailPage.textDark,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _SummaryRow extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;
  final bool isBold;

  const _SummaryRow({
    required this.label,
    required this.value,
    this.valueColor,
    this.isBold = false,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 12,
            color: Color(0xFF6B7280),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: 12,
            fontWeight: isBold ? FontWeight.w700 : FontWeight.w500,
            color: valueColor ?? RewardRedemptionDetailPage.textDark,
          ),
        ),
      ],
    );
  }
}
