import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/bottom_nav_bar.dart';

class AboutPage extends StatelessWidget {
  const AboutPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF7FBF8),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios_new,
            color: Colors.black,
            size: 20,
          ),
          onPressed: () => context.pop(),
        ),
        title: const Text(
          'Tentang Aplikasi',
          style: TextStyle(
            color: Colors.black,
            fontSize: 16,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 26, 20, 30),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  const Color(0xFFEAF5EE).withValues(alpha: 0.8),
                  const Color(0xFFDCEFE3),
                ],
              ),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFFCDE4D5), width: 1),
            ),
            child: Column(
              children: [
                const SizedBox(height: 10),

                // LOGO SOTO (Single Asset yang sudah berisi ikon, teks SOTO, & slogan)
                Image.asset(
                  'assets/images/Group-1.png',
                  width: 220,
                  height: 120,
                  fit: BoxFit.contain,
                  filterQuality: FilterQuality.high,
                  errorBuilder: (context, error, stackTrace) {
                    return const Icon(
                      Icons.recycling,
                      size: 82,
                      color: Color(0xFF159447),
                    );
                  },
                ),

                const SizedBox(height: 24),

                const _InfoRow(label: 'Versi', value: '1.0.0'),

                const SizedBox(height: 8),

                const _InfoRow(label: 'Developer', value: 'Tim SOTO'),

                const SizedBox(height: 8),

                const _InfoRow(
                  label: 'Universitas / Institusi',
                  value: 'Politeknik Elektronika Negeri Surabaya',
                ),

                const SizedBox(height: 20),

                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.9),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    'SOTO adalah solusi smart recycling untuk merubah sampah botol plastik menjadi poin yang bermanfaat.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.black.withValues(alpha: 0.75),
                      fontSize: 13,
                      height: 1.45,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;

  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      constraints: const BoxConstraints(minHeight: 42),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.92),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFBDBDBD), width: 0.8),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                color: Color(0xFF222222),
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          if (value.isNotEmpty)
            Flexible(
              child: Text(
                value,
                textAlign: TextAlign.right,
                style: const TextStyle(
                  color: Color(0xFF222222),
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
        ],
      ),
    );
  }
}
