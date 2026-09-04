import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/bottom_nav_bar.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  static const Color green = Color(0xFF2D6A4F);
  static const Color lightGreen = Color(0xFFE7F8EF);
  static const Color textDark = Color(0xFF222222);
  static const Color textGrey = Color(0xFF777777);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      bottomNavigationBar: const BottomNavBar(),
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            final horizontalPadding = constraints.maxWidth < 360 ? 14.0 : 20.0;

            return SingleChildScrollView(
              physics: const BouncingScrollPhysics(),
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

                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Halo, Andi',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 17,
                                fontWeight: FontWeight.w700,
                                color: textDark,
                              ),
                            ),
                            SizedBox(height: 3),
                            Text(
                              'Level 3 · Peduli Bumi',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(fontSize: 12, color: textGrey),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(width: 8),

                      InkWell(
                        onTap: () => context.push(AppRouter.notification),
                        borderRadius: BorderRadius.circular(20),
                        child: const Padding(
                          padding: EdgeInsets.all(6),
                          child: Icon(
                            Icons.notifications_none_rounded,
                            size: 26,
                            color: textDark,
                          ),
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
                      color: lightGreen,
                      borderRadius: BorderRadius.circular(9),
                    ),
                    child: Row(
                      children: [
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Saldo Poin',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: green,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              SizedBox(height: 4),
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    '1.250',
                                    style: TextStyle(
                                      fontSize: 24,
                                      fontWeight: FontWeight.w700,
                                      color: textDark,
                                    ),
                                  ),
                                  SizedBox(width: 5),
                                  Padding(
                                    padding: EdgeInsets.only(bottom: 3),
                                    child: Text(
                                      'Poin',
                                      style: TextStyle(
                                        fontSize: 13,
                                        color: textDark,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              SizedBox(height: 1),
                              Text(
                                '+120 poin hari ini',
                                style: TextStyle(
                                  fontSize: 11,
                                  color: green,
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
                                color: green,
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
                      const Expanded(
                        child: _InfoCard(
                          title: 'Botol Ditabung',
                          value: '86',
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
                          child: const Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Progres level',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 12,
                                  color: textDark,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),

                              SizedBox(height: 10),

                              ClipRRect(
                                borderRadius: BorderRadius.all(
                                  Radius.circular(10),
                                ),
                                child: LinearProgressIndicator(
                                  value: 0.72,
                                  minHeight: 5,
                                  backgroundColor: Color(0xFFE4E4E4),
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                    green,
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
                          onTap: () {
                            context.push(AppRouter.notification);
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
                                  color: textDark,
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
                                  color: green,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                          ],
                        ),

                        const SizedBox(height: 9),

                        const Text(
                          '2 mesin di dekat kamu',
                          style: TextStyle(fontSize: 12, color: textGrey),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 17),
                  const Text(
                    'Riwayat Terakhir',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: textDark,
                    ),
                  ),

                  const SizedBox(height: 9),

                  InkWell(
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
                      child: const Column(
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  '+ 12 Poin',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                    color: green,
                                  ),
                                ),
                              ),

                              SizedBox(width: 8),

                              Text(
                                'Mesin SOTO #21',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                  color: textDark,
                                ),
                              ),
                            ],
                          ),

                          SizedBox(height: 11),

                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  'Mesin SOTO #21',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: textGrey,
                                  ),
                                ),
                              ),

                              SizedBox(width: 8),

                              Text(
                                'Hari ini 10.30',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(fontSize: 11, color: textGrey),
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

  const _QuickMenu({
    required this.icon,
    required this.label,
    required this.onTap,
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
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: const Color(0xFFF5F5F5),
                borderRadius: BorderRadius.circular(9),
              ),
              child: Icon(icon, size: 23, color: const Color(0xFF555555)),
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
