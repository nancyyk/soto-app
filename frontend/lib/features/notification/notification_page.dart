import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/widgets/bottom_nav_bar.dart';

class NotificationPage extends StatelessWidget {
  const NotificationPage({super.key});

  static const Color green = Color(0xFF2D6A4F);
  static const Color lightGreen = Color(0xFFE8F7F0);

  @override
  Widget build(BuildContext context) {
    final notifications = const [
      _NotificationItem(
        icon: Icons.check_circle_outline,
        title: 'Reward Berhasil',
        description: 'Kamu berhasil menukar reward\nVoucher 10.000',
        time: '2 jam lalu',
      ),
      _NotificationItem(
        icon: Icons.add,
        title: 'Poin Bertambah',
        description: '+24 Poin dari Mesin SOTO #21',
        time: '2 jam lalu',
      ),
      _NotificationItem(
        icon: Icons.warning_amber_rounded,
        title: 'Mesin Penuh',
        description: 'Mesin SOTO #06 sedang penuh',
        time: '5 jam lalu',
        warning: true,
      ),
      _NotificationItem(
        icon: Icons.card_giftcard_outlined,
        title: 'Promo Spesial',
        description: 'Tukar poin lebih hemat hari ini',
        time: '5 hari lalu',
      ),
    ];

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          onPressed: () => context.pop(),
          icon: const Icon(
            Icons.arrow_back_ios_new,
            color: Colors.black,
            size: 20,
          ),
        ),
        title: const Text(
          'Notifikasi',
          style: TextStyle(
            color: Colors.black,
            fontSize: 16,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      body: ListView.separated(
        padding: const EdgeInsets.fromLTRB(18, 26, 18, 24),
        itemCount: notifications.length,
        separatorBuilder: (context, index) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final item = notifications[index];

          return Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border.all(color: const Color(0xFFE0E0E0)),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    color: lightGreen,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    item.icon,
                    size: 22,
                    color: item.warning ? const Color(0xFFFFA726) : green,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.title,
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: Colors.black,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        item.description,
                        style: const TextStyle(
                          fontSize: 11,
                          height: 1.3,
                          color: Colors.black87,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        item.time,
                        style: const TextStyle(
                          fontSize: 10,
                          color: Colors.black54,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

class _NotificationItem {
  final IconData icon;
  final String title;
  final String description;
  final String time;
  final bool warning;

  const _NotificationItem({
    required this.icon,
    required this.title,
    required this.description,
    required this.time,
    this.warning = false,
  });
}
