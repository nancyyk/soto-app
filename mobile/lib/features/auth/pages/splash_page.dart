import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../data/auth_service.dart';

class SplashPage extends StatefulWidget {
  const SplashPage({super.key});

  @override
  State<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<SplashPage>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;
  Timer? _navigationTimer;

  @override
  void initState() {
    super.initState();

    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );

    _fadeAnimation = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeIn,
    );

    _controller.forward();

    _navigationTimer = Timer(const Duration(milliseconds: 2500), () async {
      if (!mounted) return;
      
      final authService = AuthService();
      final isLoggedIn = await authService.isLoggedIn();
      
      if (!mounted) return;
      
      if (isLoggedIn) {
        final user = await authService.getUser();
        if (user != null && user['rfid_uid'] != null && user['rfid_uid'].toString().isNotEmpty) {
          context.go('/home');
        } else {
          context.go('/rfid');
        }
      } else {
        context.go('/onboarding');
      }
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    _navigationTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SizedBox.expand(
        child: Stack(
          fit: StackFit.expand,
          children: [
            // Background dari Figma
            Image.asset(
              'assets/images/splash-screen.jpg',
              fit: BoxFit.cover,
            ),

            // Group 1 dari Figma
            Center(
              child: FadeTransition(
                opacity: _fadeAnimation,
                child: Image.asset(
                  'assets/images/Group-1.png',
                  width: 210,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}