import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../data/reward_redemption_data.dart';
import '../data/reward_service.dart';
import '../widgets/reward_image.dart';

class RewardHistoryPage extends StatefulWidget {
  const RewardHistoryPage({super.key});

  static const Color green = Color(0xFF2D6A4F);
  static const Color textDark = Color(0xFF263238);

  @override
  State<RewardHistoryPage> createState() => _RewardHistoryPageState();
}

class _RewardHistoryPageState extends State<RewardHistoryPage> {
  final _rewardService = RewardService();
  List<RewardRedemptionData> _redemptions = [];
  bool _isLoading = true;
  String _selectedFilter = 'Semua';

  static const List<String> _filters = [
    'Semua',
    'Proses',
    'Pengiriman',
    'Selesai',
  ];

  @override
  void initState() {
    super.initState();
    _loadRedemptions();
  }

  Future<void> _loadRedemptions() async {
    try {
      final data = await _rewardService.getMyRedemptions();
      if (mounted) {
        setState(() {
          _redemptions = data;
          _isLoading = false;
        });
      }
    } catch (e) {
      debugPrint('Error loading redemption history: $e');
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  List<RewardRedemptionData> get _filteredRedemptions {
    if (_selectedFilter == 'Semua') {
      return _redemptions;
    }
    return _redemptions.where((item) {
      return item.normalizedStatus.toLowerCase() ==
          _selectedFilter.toLowerCase();
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAF9),
      body: SafeArea(
        child: Column(
          children: [
            // APP BAR
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 12),
              child: Row(
                children: [
                  InkWell(
                    borderRadius: BorderRadius.circular(20),
                    onTap: () {
                      if (context.canPop()) {
                        context.pop();
                      } else {
                        context.go(AppRouter.reward);
                      }
                    },
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
                        color: RewardHistoryPage.textDark,
                      ),
                    ),
                  ),
                  const Expanded(
                    child: Center(
                      child: Text(
                        'Riwayat Penukaran',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: RewardHistoryPage.textDark,
                        ),
                      ),
                    ),
                  ),
                  InkWell(
                    borderRadius: BorderRadius.circular(20),
                    onTap: () {
                      setState(() => _isLoading = true);
                      _loadRedemptions();
                    },
                    child: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        border: Border.all(color: const Color(0xFFE4E6E4)),
                      ),
                      child: const Icon(
                        Icons.refresh_rounded,
                        size: 18,
                        color: RewardHistoryPage.green,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // FILTER TABS
            Container(
              height: 40,
              margin: const EdgeInsets.symmetric(vertical: 8),
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: _filters.length,
                separatorBuilder: (context, index) => const SizedBox(width: 8),
                itemBuilder: (context, index) {
                  final filter = _filters[index];
                  final isSelected = _selectedFilter == filter;

                  return GestureDetector(
                    onTap: () => setState(() => _selectedFilter = filter),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: isSelected
                            ? RewardHistoryPage.green
                            : Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: isSelected
                              ? RewardHistoryPage.green
                              : const Color(0xFFE2E5E2),
                        ),
                      ),
                      child: Text(
                        filter,
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight:
                              isSelected ? FontWeight.w700 : FontWeight.w500,
                          color: isSelected
                              ? Colors.white
                              : const Color(0xFF555555),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),

            const SizedBox(height: 8),

            // CONTENT / LIST
            Expanded(
              child: _isLoading
                  ? const Center(
                      child: CircularProgressIndicator(
                        color: RewardHistoryPage.green,
                      ),
                    )
                  : RefreshIndicator(
                      color: RewardHistoryPage.green,
                      onRefresh: _loadRedemptions,
                      child: _filteredRedemptions.isEmpty
                          ? _buildEmptyState()
                          : ListView.builder(
                              physics: const AlwaysScrollableScrollPhysics(
                                parent: BouncingScrollPhysics(),
                              ),
                              padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
                              itemCount: _filteredRedemptions.length,
                              itemBuilder: (context, index) {
                                final item = _filteredRedemptions[index];
                                return _RedemptionCard(
                                  redemption: item,
                                  onTap: () async {
                                    await context.push(
                                      AppRouter.rewardRedemptionDetail,
                                      extra: item,
                                    );
                                    _loadRedemptions();
                                  },
                                );
                              },
                            ),
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 80),
        Center(
          child: Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: const Color(0xFFE8F5E9),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.receipt_long_rounded,
              color: RewardHistoryPage.green,
              size: 40,
            ),
          ),
        ),
        const SizedBox(height: 18),
        Center(
          child: Text(
            _selectedFilter == 'Semua'
                ? 'Belum Ada Penukaran Reward'
                : 'Tidak ada status "$_selectedFilter"',
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: RewardHistoryPage.textDark,
            ),
          ),
        ),
        const SizedBox(height: 6),
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 40),
          child: Text(
            'Kumpulkan poin dari membuang botol plastik dan tukarkan dengan berbagai reward menarik!',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 13,
              color: Color(0xFF7A7A7A),
              height: 1.4,
            ),
          ),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: () => context.go(AppRouter.reward),
            style: ElevatedButton.styleFrom(
              backgroundColor: RewardHistoryPage.green,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              elevation: 0,
            ),
            icon: const Icon(Icons.card_giftcard, size: 18),
            label: const Text(
              'Tukar Reward Sekarang',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
            ),
          ),
        ),
      ],
    );
  }
}

class _RedemptionCard extends StatelessWidget {
  final RewardRedemptionData redemption;
  final VoidCallback onTap;

  const _RedemptionCard({
    required this.redemption,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final reward = redemption.reward;
    final status = redemption.normalizedStatus;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE8EBE8)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x05000000),
            blurRadius: 10,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // HEADER CARD: Tanggal & Badge Status
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      redemption.formattedDate,
                      style: const TextStyle(
                        fontSize: 11,
                        color: Color(0xFF888888),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    _StatusBadge(status: status),
                  ],
                ),

                const SizedBox(height: 12),
                const Divider(height: 1, color: Color(0xFFF0F2F0)),
                const SizedBox(height: 12),

                // BODY: Gambar & Info Reward
                Row(
                  children: [
                    Container(
                      width: 64,
                      height: 64,
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
                          color: RewardHistoryPage.green,
                          size: 28,
                        ),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (reward?.category != null &&
                              reward!.category.isNotEmpty) ...[
                            Text(
                              reward.category,
                              style: const TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: RewardHistoryPage.green,
                              ),
                            ),
                            const SizedBox(height: 2),
                          ],
                          Text(
                            reward?.title ?? 'Reward #${redemption.rewardId}',
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                              color: RewardHistoryPage.textDark,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            '-${redemption.points} Poin',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFFE53935),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                // INFO EKSPEDISI / RESI JIKA ADA
                if (redemption.expedition != null ||
                    redemption.trackingNumber != null) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF4F7FC),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.local_shipping_outlined,
                          size: 16,
                          color: Color(0xFF2563EB),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            '${redemption.expedition ?? 'Ekspedisi'}: ${redemption.trackingNumber ?? 'Menunggu Resi'}',
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF1E3A8A),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 10),

                // ACTION: Tap to see details
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: const [
                    Text(
                      'Lihat Detail Status',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: RewardHistoryPage.green,
                      ),
                    ),
                    SizedBox(width: 4),
                    Icon(
                      Icons.arrow_forward_ios_rounded,
                      size: 11,
                      color: RewardHistoryPage.green,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;

  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color fg;
    IconData icon;

    switch (status.toLowerCase()) {
      case 'selesai':
        bg = const Color(0xFFDCFCE7);
        fg = const Color(0xFF166534);
        icon = Icons.check_circle_outline_rounded;
        break;
      case 'pengiriman':
        bg = const Color(0xFFDBEAFE);
        fg = const Color(0xFF1E40AF);
        icon = Icons.local_shipping_outlined;
        break;
      case 'proses':
      default:
        bg = const Color(0xFFFEF9C3);
        fg = const Color(0xFF854D0E);
        icon = Icons.hourglass_top_rounded;
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: fg),
          const SizedBox(width: 4),
          Text(
            status,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: fg,
            ),
          ),
        ],
      ),
    );
  }
}
