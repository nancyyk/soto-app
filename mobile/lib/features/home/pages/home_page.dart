import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/bottom_nav_bar.dart';
import '../../auth/data/auth_service.dart';
import '../data/home_service.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  static const Color green = Color(0xFF2D6A4F);
  static const Color lightGreen = Color(0xFFE7F8EF);
  static const Color textDark = Color(0xFF222222);
  static const Color textGrey = Color(0xFF777777);

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  final _authService = AuthService();
  final _homeService = HomeService();

  Map<String, dynamic>? _user;
  int _saldoPoin = 0;
  int _totalBotol = 0;
  int _unreadNotifCount = 0;
  Map<String, dynamic>? _latestTransaction;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final user = await _authService.getUser();
      if (user != null && user['id'] != null) {
        final results = await Future.wait([
          _homeService.getUserPoints(user['id']),
          _homeService.getUnreadNotificationCount(),
        ]);
        final data = results[0] as Map<String, dynamic>;
        final unreadCount = results[1] as int;

        if (mounted) {
          setState(() {
            _user = user;
            _saldoPoin = data['saldo_poin'] ?? 0;
            _unreadNotifCount = unreadCount;
            if (data['total_botol'] != null) {
              _totalBotol = (data['total_botol'] as num).toInt();
            } else if (data['transactions'] != null && data['transactions']['data'] != null) {
              final txs = data['transactions']['data'] as List;
              _totalBotol = txs.fold(0, (sum, item) => sum + (item['jumlah_botol'] as int? ?? 0));
            }
            if (data['transactions'] != null && data['transactions']['data'] != null) {
              final txs = data['transactions']['data'] as List;
              _latestTransaction = txs.isNotEmpty ? Map<String, dynamic>.from(txs.first as Map) : null;
            } else {
              _latestTransaction = null;
            }
            _isLoading = false;
          });
        }
      } else {
        if (mounted) setState(() => _isLoading = false);
      }
    } catch (e) {
      debugPrint('Error loading home data: $e');
      if (mounted) setState(() => _isLoading = false);
    }
  }

  String _formatTxDateTime(String? iso) {
    if (iso == null || iso.isEmpty) return '-';
    try {
      final dt = DateTime.parse(iso).toLocal();
      final now = DateTime.now();
      final isToday = dt.year == now.year && dt.month == now.month && dt.day == now.day;
      final isYesterday = dt.year == now.year && dt.month == now.month && dt.day == now.day - 1;
      final timeStr = '${dt.hour.toString().padLeft(2, '0')}.${dt.minute.toString().padLeft(2, '0')} WIB';
      if (isToday) {
        return 'Hari ini $timeStr';
      } else if (isYesterday) {
        return 'Kemarin $timeStr';
      } else {
        final months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return '${dt.day} ${months[dt.month - 1]} $timeStr';
      }
    } catch (_) {
      return iso;
    }
  }

  String _getMachineName(Map<String, dynamic> tx) {
    if (tx['machine'] != null && tx['machine']['nama_lokasi'] != null) {
      return tx['machine']['nama_lokasi'].toString();
    }
    return 'Mesin SOTO #${tx['machine_id'] ?? '-'}';
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: HomePage.green)),
      );
    }

    final userName = _user?['nama'] ?? 'Pengguna';
    final levelInfo = _LevelInfo.calculate(_totalBotol);

    return Scaffold(
      backgroundColor: Colors.white,
      bottomNavigationBar: const BottomNavBar(),
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            final horizontalPadding = constraints.maxWidth < 360 ? 14.0 : 20.0;

            return RefreshIndicator(
              onRefresh: _loadData,
              color: HomePage.green,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                padding: EdgeInsets.fromLTRB(
                  horizontalPadding,
                  34,
                  horizontalPadding,
                  24,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        ClipOval(
                          child: Image.asset(
                            'assets/images/profile.png',
                            width: 46,
                            height: 46,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) {
                              return Container(
                                width: 46,
                                height: 46,
                                color: const Color(0xFFE8C39E),
                                alignment: Alignment.center,
                                child: const Icon(
                                  Icons.person,
                                  color: Colors.white,
                                  size: 28,
                                ),
                              );
                            },
                          ),
                        ),

                        const SizedBox(width: 11),

                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Halo, $userName',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w700,
                                  color: HomePage.textDark,
                                ),
                              ),
                              const SizedBox(height: 3),
                              Text(
                                'Level ${levelInfo.level} · ${levelInfo.title}',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(fontSize: 12, color: HomePage.textGrey),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(width: 8),

                        InkWell(
                          onTap: () async {
                            await context.push(AppRouter.notification);
                            _loadData();
                          },
                          borderRadius: BorderRadius.circular(20),
                          child: Stack(
                            clipBehavior: Clip.none,
                            children: [
                              const Padding(
                                padding: EdgeInsets.all(6),
                                child: Icon(
                                  Icons.notifications_none_rounded,
                                  size: 26,
                                  color: HomePage.textDark,
                                ),
                              ),
                              if (_unreadNotifCount > 0)
                                Positioned(
                                  top: 3,
                                  right: 3,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                                    constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFE63946),
                                      borderRadius: BorderRadius.circular(10),
                                      border: Border.all(color: Colors.white, width: 1.5),
                                    ),
                                    alignment: Alignment.center,
                                    child: Text(
                                      _unreadNotifCount > 9 ? '9+' : '$_unreadNotifCount',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 9,
                                        fontWeight: FontWeight.w700,
                                        height: 1.0,
                                      ),
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 16),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 15,
                        vertical: 11,
                      ),
                      decoration: BoxDecoration(
                        color: HomePage.lightGreen,
                        borderRadius: BorderRadius.circular(9),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Saldo Poin',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: HomePage.green,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Text(
                                      '$_saldoPoin',
                                      style: const TextStyle(
                                        fontSize: 24,
                                        fontWeight: FontWeight.w700,
                                        color: HomePage.textDark,
                                      ),
                                    ),
                                    const SizedBox(width: 5),
                                    const Padding(
                                      padding: EdgeInsets.only(bottom: 3),
                                      child: Text(
                                        'Poin',
                                        style: TextStyle(
                                          fontSize: 13,
                                          color: HomePage.textDark,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 1),
                                const Text(
                                  'Tarik ke bawah untuk refresh',
                                  style: TextStyle(
                                    fontSize: 10,
                                    color: HomePage.green,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),

                          const SizedBox(width: 8),

                          Image.asset(
                            'assets/images/botol1.png',
                            width: 72,
                            height: 72,
                            fit: BoxFit.contain,
                            errorBuilder: (context, error, stackTrace) {
                              return const SizedBox(
                                width: 72,
                                height: 72,
                                child: Icon(
                                  Icons.recycling,
                                  size: 48,
                                  color: HomePage.green,
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Expanded(
                          child: _InfoCard(
                            title: 'Botol Ditabung',
                            value: '$_totalBotol',
                            suffix: 'Botol',
                          ),
                        ),

                        const SizedBox(width: 12),

                        Expanded(
                          child: Container(
                            height: 64,
                            padding: const EdgeInsets.symmetric(
                              horizontal: 13,
                              vertical: 10,
                            ),
                            decoration: BoxDecoration(
                              border: Border.all(color: const Color(0xFFE1E1E1)),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text(
                                      'Progres level',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: HomePage.textDark,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                    Text(
                                      levelInfo.isMax
                                          ? 'Maksimal'
                                          : '${levelInfo.currentInTier}/${levelInfo.targetInTier}',
                                      style: const TextStyle(
                                        fontSize: 11,
                                        color: HomePage.green,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ],
                                ),

                                ClipRRect(
                                  borderRadius: const BorderRadius.all(
                                    Radius.circular(10),
                                  ),
                                  child: LinearProgressIndicator(
                                    value: levelInfo.progress,
                                    minHeight: 5,
                                    backgroundColor: const Color(0xFFE4E4E4),
                                    valueColor: const AlwaysStoppedAnimation<Color>(
                                      HomePage.green,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 17),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Flexible(
                          child: _QuickMenu(
                            icon: Icons.history,
                            label: 'Riwayat',
                            onTap: () {
                              context.go(AppRouter.history);
                            },
                          ),
                        ),
                        Flexible(
                          child: _QuickMenu(
                            icon: Icons.card_giftcard,
                            label: 'Reward',
                            onTap: () {
                              context.go(AppRouter.reward);
                            },
                          ),
                        ),
                        Flexible(
                          child: _QuickMenu(
                            icon: Icons.location_on_outlined,
                            label: 'Lokasi Mesin',
                            onTap: () {
                              context.push(AppRouter.machine);
                            },
                          ),
                        ),
                        Flexible(
                          child: _QuickMenu(
                            icon: Icons.notifications_none,
                            label: 'Notifikasi',
                            badgeCount: _unreadNotifCount,
                            onTap: () async {
                              await context.push(AppRouter.notification);
                              _loadData();
                            },
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 17),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.fromLTRB(13, 12, 13, 12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8F8F8),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Expanded(
                                child: Text(
                                  'Mesin Terdekat',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w700,
                                    color: HomePage.textDark,
                                  ),
                                ),
                              ),

                              const SizedBox(width: 8),

                              GestureDetector(
                                onTap: () {
                                  context.push(AppRouter.machine);
                                },
                                child: const Text(
                                  'Lihat Semua',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: HomePage.green,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 9),

                          const Text(
                            '2 mesin di dekat kamu',
                            style: TextStyle(fontSize: 12, color: HomePage.textGrey),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 17),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Riwayat Terakhir',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: HomePage.textDark,
                          ),
                        ),
                        if (_latestTransaction != null)
                          GestureDetector(
                            onTap: () {
                              context.go(AppRouter.history);
                            },
                            child: const Text(
                              'Lihat Semua',
                              style: TextStyle(
                                fontSize: 11,
                                color: HomePage.green,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                      ],
                    ),

                    const SizedBox(height: 9),

                    _latestTransaction == null
                        ? Container(
                            width: double.infinity,
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF9F9F9),
                              border: Border.all(color: const Color(0xFFEBEBEB)),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Column(
                              children: [
                                Icon(
                                  Icons.receipt_long_outlined,
                                  size: 32,
                                  color: HomePage.textGrey,
                                ),
                                SizedBox(height: 6),
                                Text(
                                  'Belum ada transaksi',
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    color: HomePage.textDark,
                                  ),
                                ),
                                SizedBox(height: 2),
                                Text(
                                  'Tukarkan botol pertamamu di mesin SOTO!',
                                  style: TextStyle(fontSize: 11, color: HomePage.textGrey),
                                ),
                              ],
                            ),
                          )
                        : InkWell(
                            onTap: () {
                              context.go(AppRouter.history);
                            },
                            borderRadius: BorderRadius.circular(8),
                            child: Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(13),
                              decoration: BoxDecoration(
                                border: Border.all(color: const Color(0xFFE1E1E1)),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Column(
                                children: [
                                  Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          '+ ${_latestTransaction!['poin_diperoleh'] ?? 0} Poin',
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            fontSize: 13,
                                            fontWeight: FontWeight.w700,
                                            color: HomePage.green,
                                          ),
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: HomePage.lightGreen,
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: Text(
                                          '${_latestTransaction!['jumlah_botol'] ?? 0} Botol',
                                          style: const TextStyle(
                                            fontSize: 11,
                                            fontWeight: FontWeight.w600,
                                            color: HomePage.green,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),

                                  const SizedBox(height: 10),

                                  Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          _getMachineName(_latestTransaction!),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            fontSize: 12,
                                            fontWeight: FontWeight.w500,
                                            color: HomePage.textDark,
                                          ),
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        _formatTxDateTime(
                                          _latestTransaction!['created_at']?.toString(),
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(
                                          fontSize: 11,
                                          color: HomePage.textGrey,
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ),

                    const SizedBox(height: 8),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final String title;
  final String value;
  final String suffix;

  const _InfoCard({
    required this.title,
    required this.value,
    required this.suffix,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 64,
      padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 10),
      decoration: BoxDecoration(
        border: Border.all(color: const Color(0xFFE1E1E1)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 12,
              color: HomePage.textDark,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 3),
          Expanded(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Flexible(
                  child: FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.bottomLeft,
                    child: Text(
                      value,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: HomePage.green,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 4),
                Padding(
                  padding: const EdgeInsets.only(bottom: 2),
                  child: Text(
                    suffix,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 11,
                      color: HomePage.textGrey,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickMenu extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final int badgeCount;

  const _QuickMenu({
    required this.icon,
    required this.label,
    required this.onTap,
    this.badgeCount = 0,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: SizedBox(
        width: 72,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF5F5F5),
                    borderRadius: BorderRadius.circular(9),
                  ),
                  child: Icon(icon, size: 23, color: const Color(0xFF555555)),
                ),
                if (badgeCount > 0)
                  Positioned(
                    top: -2,
                    right: -2,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                      constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE63946),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.white, width: 1.5),
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        badgeCount > 9 ? '9+' : '$badgeCount',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 9,
                          fontWeight: FontWeight.w700,
                          height: 1.0,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              label,
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 10,
                color: HomePage.textDark,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LevelInfo {
  final int level;
  final String title;
  final double progress;
  final int currentInTier;
  final int targetInTier;
  final bool isMax;

  const _LevelInfo({
    required this.level,
    required this.title,
    required this.progress,
    required this.currentInTier,
    required this.targetInTier,
    this.isMax = false,
  });

  static _LevelInfo calculate(int totalBotol) {
    if (totalBotol < 20) {
      return _LevelInfo(
        level: 1,
        title: 'Pemula',
        progress: (totalBotol / 20.0).clamp(0.0, 1.0),
        currentInTier: totalBotol,
        targetInTier: 20,
      );
    } else if (totalBotol < 50) {
      return _LevelInfo(
        level: 2,
        title: 'Pejuang Lingkungan',
        progress: ((totalBotol - 20) / 30.0).clamp(0.0, 1.0),
        currentInTier: totalBotol - 20,
        targetInTier: 30,
      );
    } else if (totalBotol < 100) {
      return _LevelInfo(
        level: 3,
        title: 'Pahlawan Daur Ulang',
        progress: ((totalBotol - 50) / 50.0).clamp(0.0, 1.0),
        currentInTier: totalBotol - 50,
        targetInTier: 50,
      );
    } else if (totalBotol < 200) {
      return _LevelInfo(
        level: 4,
        title: 'Sahabat Bumi',
        progress: ((totalBotol - 100) / 100.0).clamp(0.0, 1.0),
        currentInTier: totalBotol - 100,
        targetInTier: 100,
      );
    } else {
      return const _LevelInfo(
        level: 5,
        title: 'Legenda Hijau',
        progress: 1.0,
        currentInTier: 200,
        targetInTier: 200,
        isMax: true,
      );
    }
  }
}
