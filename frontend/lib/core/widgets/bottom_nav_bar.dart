import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class BottomNavBar extends StatelessWidget {
  const BottomNavBar({super.key});

  @override
  Widget build(BuildContext context) {
    final currentPath = GoRouterState.of(context).uri.path;

    int currentIndex = 0;

    if (currentPath.startsWith('/history')) {
      currentIndex = 1;
    } else if (currentPath.startsWith('/reward')) {
      currentIndex = 2;
    } else if (currentPath.startsWith('/profile') || currentPath == '/about') {
      currentIndex = 3;
    } else {
      currentIndex = 0;
    }

    const items = [
      _NavItem(
        icon: Icons.home_outlined,
        activeIcon: Icons.home,
        label: 'Home',
        route: '/home',
      ),
      _NavItem(
        icon: Icons.history_outlined,
        activeIcon: Icons.history,
        label: 'Riwayat',
        route: '/history',
      ),
      _NavItem(
        icon: Icons.card_giftcard_outlined,
        activeIcon: Icons.card_giftcard,
        label: 'Reward',
        route: '/reward',
      ),
      _NavItem(
        icon: Icons.person_outline,
        activeIcon: Icons.person,
        label: 'Profile',
        route: '/profile',
      ),
    ];

    return SafeArea(
      top: false,
      child: Container(
        height: 68,
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(
            top: BorderSide(
              color: Color(0xFFE5E5E5),
              width: 1,
            ),
          ),
        ),
        child: Row(
          children: List.generate(
            items.length,
            (index) {
              final item = items[index];
              final isActive = index == currentIndex;

              return Expanded(
                child: InkWell(
                  onTap: () {
                    if (!isActive) {
                      context.go(item.route);
                    }
                  },
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        isActive ? item.activeIcon : item.icon,
                        size: 22,
                        color: isActive
                            ? const Color(0xFF2D6A4F)
                            : const Color(0xFFBDBDBD),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        item.label,
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: isActive
                              ? FontWeight.w600
                              : FontWeight.w400,
                          color: isActive
                              ? const Color(0xFF2D6A4F)
                              : const Color(0xFFBDBDBD),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}

class _NavItem {
  final IconData icon;
  final IconData activeIcon;
  final String label;
  final String route;

  const _NavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
    required this.route,
  });
}