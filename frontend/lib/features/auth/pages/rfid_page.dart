import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/app_button.dart';

class RFIDPage extends StatefulWidget {
  const RFIDPage({super.key});

  @override
  State<RFIDPage> createState() => _RFIDPageState();
}

class _RFIDPageState extends State<RFIDPage> {
  bool _isConnected = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SizedBox.expand(
        child: Stack(
          fit: StackFit.expand,
          children: [
            // Background sesuai Figma
            Image.asset('assets/images/background-2.jpg', fit: BoxFit.cover),

            // Konten RFID
            SafeArea(
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 420),
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 21,
                      vertical: 20,
                    ),
                    child: Column(
                      children: [
                        const SizedBox(height: 20),

                        // RFID CARD ILLUSTRATION
                        Image.asset(
                          'assets/images/RFIDcard.png',
                          width: 190,
                          height: 190,
                          fit: BoxFit.contain,
                        ),

                        const SizedBox(height: 8),

                        // TITLE
                        const Text(
                          'Hubungkan Kartu RFID',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF111111),
                          ),
                        ),

                        const SizedBox(height: 8),

                        // DESCRIPTION
                        const Padding(
                          padding: EdgeInsets.symmetric(horizontal: 4),
                          child: Text(
                            'Tempelkan kartu RFID kamu ke\n'
                            'mesin untuk mulai menabung\n'
                            'botol',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w400,
                              color: Color(0xFF111111),
                              height: 1.35,
                            ),
                          ),
                        ),

                        const SizedBox(height: 34),

                        // STATUS
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 11,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.65),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'Status',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                  color: Color(0xFF111111),
                                ),
                              ),

                              Text(
                                _isConnected ? 'Terhubung' : 'Belum Terhubung',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                  color: _isConnected
                                      ? const Color(0xFF2D6A4F)
                                      : const Color(0xFFF59E0B),
                                ),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: 12),

                        // RFID BUTTON
                        SizedBox(
                          width: double.infinity,
                          height: 44,
                          child: AppButton(
                            text: _isConnected
                                ? 'Lanjut ke Beranda'
                                : 'Hubungkan RFID',
                            onPressed: () {
                              if (!_isConnected) {
                                setState(() {
                                  _isConnected = true;
                                });
                                return;
                              }

                              context.go(AppRouter.home);
                            },
                            backgroundColor: const Color(0xFF2D6A4F),
                            textColor: Colors.white,
                            height: 44,
                          ),
                        ),

                        const SizedBox(height: 14),

                        // USER GUIDE
                        TextButton(
                          onPressed: () {
                            // Nanti bisa diarahkan ke halaman petunjuk
                          },
                          style: TextButton.styleFrom(
                            padding: EdgeInsets.zero,
                            minimumSize: Size.zero,
                            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          ),
                          child: const Text(
                            'Petunjuk Penggunaan',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF0891B2),
                            ),
                          ),
                        ),

                        const SizedBox(height: 20),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
