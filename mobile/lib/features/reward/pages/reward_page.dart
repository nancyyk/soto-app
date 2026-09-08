import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/bottom_nav_bar.dart';
import '../data/reward_data.dart';

class RewardPage extends StatefulWidget {
  const RewardPage({super.key});

  static const Color green = Color(0xFF2D6A4F);
  static const Color textDark = Color(0xFF263238);

  static const List<RewardData> allRewards = [
    RewardData(
      id: 'RWD-001',
      title: 'Voucher Belanja Rp25.000',
      description:
          'Voucher belanja senilai Rp25.000 yang dapat digunakan untuk kebutuhan sehari-hari.',
      points: 500,
      category: 'Voucher',
      imagePath: 'assets/images/voucher1.png',
    ),
    RewardData(
      id: 'RWD-002',
      title: 'OVO Cash Rp50.000',
      description:
          'Nikmati saldo OVO Cash senilai Rp50.000 dengan menukarkan poin yang kamu kumpulkan.',
      points: 900,
      category: 'E-Wallet',
      imagePath: 'assets/images/ovo1.png',
    ),
    RewardData(
      id: 'RWD-003',
      title: 'Pulsa Rp20.000',
      description:
          'Tukarkan poin kamu dengan pulsa senilai Rp20.000 untuk nomor pilihanmu.',
      points: 400,
      category: 'Pulsa',
      imagePath: 'assets/images/pulsa1.png',
    ),
    RewardData(
      id: 'RWD-004',
      title: 'Tumbler SOTO',
      description:
          'Tumbler reusable eksklusif untuk kamu yang terus peduli terhadap lingkungan.',
      points: 1200,
      category: 'Merchandise',
      imagePath: 'assets/images/tumbler1.png',
    ),
  ];

  @override
  State<RewardPage> createState() => _RewardPageState();
}

class _RewardPageState extends State<RewardPage> {
  final TextEditingController _searchController = TextEditingController();
  String _selectedFilter = 'Semua';

  static const List<String> _filters = [
    'Semua',
    'Voucher',
    'Pulsa',
    'E-Wallet',
  ];

  List<RewardData> get _filteredRewards {
    final query = _searchController.text.trim().toLowerCase();

    return RewardPage.allRewards.where((reward) {
      final matchesFilter =
          _selectedFilter == 'Semua' || reward.category == _selectedFilter;

      if (!matchesFilter) {
        return false;
      }

      if (query.isEmpty) {
        return true;
      }

      final haystack = [
        reward.title,
        reward.category,
        reward.description,
      ].join(' ').toLowerCase();

      return haystack.contains(query);
    }).toList();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAF9),
      bottomNavigationBar: const BottomNavBar(),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 28, 20, 12),
              child: Row(
                children: [
                  InkWell(
                    borderRadius: BorderRadius.circular(20),
                    onTap: () => context.go(AppRouter.home),
                    child: const Padding(
                      padding: EdgeInsets.all(8),
                      child: Icon(
                        Icons.arrow_back_ios_new,
                        size: 20,
                        color: RewardPage.textDark,
                      ),
                    ),
                  ),
                  const Expanded(
                    child: Center(
                      child: Text(
                        'Reward',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                          color: RewardPage.textDark,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 36),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 12),
              child: Container(
                height: 48,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE4E6E4)),
                ),
                child: TextField(
                  controller: _searchController,
                  onChanged: (_) => setState(() {}),
                  decoration: const InputDecoration(
                    border: InputBorder.none,
                    hintText: 'Cari Reward...',
                    hintStyle: TextStyle(
                      fontSize: 13,
                      color: Color(0xFF999999),
                    ),
                    prefixIcon: Icon(
                      Icons.search,
                      size: 21,
                      color: Color(0xFF8E8E8E),
                    ),
                    contentPadding: EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 13,
                    ),
                  ),
                ),
              ),
            ),
            SizedBox(
              height: 42,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: _filters.length,
                separatorBuilder: (context, index) => const SizedBox(width: 8),
                itemBuilder: (context, index) {
                  final filter = _filters[index];
                  final active = filter == _selectedFilter;

                  return GestureDetector(
                    onTap: () {
                      setState(() {
                        _selectedFilter = filter;
                      });
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 10,
                      ),
                      decoration: BoxDecoration(
                        color: active ? RewardPage.green : Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: active
                            ? null
                            : Border.all(color: const Color(0xFFE2E5E3)),
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        filter,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: active
                              ? Colors.white
                              : const Color(0xFF777777),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 18),
            Expanded(
              child: _filteredRewards.isEmpty
                  ? const Center(
                      child: Text(
                        'Reward tidak ditemukan',
                        style: TextStyle(
                          fontSize: 14,
                          color: Color(0xFF7A7A7A),
                        ),
                      ),
                    )
                  : ListView.builder(
                      physics: const BouncingScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
                      itemCount: _filteredRewards.length,
                      itemBuilder: (context, index) {
                        final reward = _filteredRewards[index];

                        return Padding(
                          padding: const EdgeInsets.only(bottom: 14),
                          child: _RewardCard(
                            reward: reward,
                            onTap: () {
                              context.push(
                                AppRouter.rewardDetail,
                                extra: reward,
                              );
                            },
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RewardCard extends StatelessWidget {
  final RewardData reward;
  final VoidCallback onTap;

  const _RewardCard({required this.reward, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: const Color(0xFFE7EAE7)),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                width: 80,
                height: 80,
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFF5F7F5),
                  borderRadius: BorderRadius.circular(14),
                ),
                clipBehavior: Clip.antiAlias,
                child: Image.asset(
                  reward.imagePath,
                  fit: BoxFit.contain,
                  filterQuality: FilterQuality.high,
                  errorBuilder: (context, error, stackTrace) {
                    return const Center(
                      child: Text(
                        'Reward',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: RewardPage.green,
                        ),
                      ),
                    );
                  },
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      reward.category,
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: RewardPage.green,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      reward.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: RewardPage.textDark,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${reward.points} Poin',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: RewardPage.green,
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(
                Icons.chevron_right,
                color: Color(0xFFB0B0B0),
                size: 22,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
