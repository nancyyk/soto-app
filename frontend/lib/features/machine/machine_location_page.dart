import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/widgets/bottom_nav_bar.dart';

class MachineLocationPage extends StatelessWidget {
  const MachineLocationPage({super.key});

  static const Color green = Color(0xFF2D6A4F);

  @override
  Widget build(BuildContext context) {
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
          'Lokasi Mesin',
          style: TextStyle(
            color: Colors.black,
            fontSize: 16,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            // PETA
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 22, 18, 0),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(18),
                child: SizedBox(
                  width: double.infinity,
                  height: 295,
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      Image.asset('assets/images/peta1.png', fit: BoxFit.cover),

                      // Marker 1
                      const Positioned(left: 68, top: 48, child: _MapMarker()),

                      // Marker 2
                      const Positioned(
                        right: 42,
                        top: 100,
                        child: _MapMarker(),
                      ),

                      // Marker aktif
                      const Positioned(
                        left: 115,
                        top: 142,
                        child: _MapMarker(active: true),
                      ),

                      // Marker 4
                      const Positioned(
                        left: 48,
                        bottom: 70,
                        child: _MapMarker(),
                      ),

                      // Marker 5
                      const Positioned(
                        right: 48,
                        bottom: 50,
                        child: _MapMarker(),
                      ),
                    ],
                  ),
                ),
              ),
            ),

            // DETAIL MESIN
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(18, 16, 18, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Mesin SOTO #21',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: Colors.black,
                      ),
                    ),

                    const SizedBox(height: 4),

                    const Text(
                      'Jalan Merdeka No. 10',
                      style: TextStyle(fontSize: 13, color: Color(0xFF666666)),
                    ),

                    const SizedBox(height: 12),

                    Row(
                      children: [
                        const Icon(
                          Icons.location_on_outlined,
                          size: 18,
                          color: green,
                        ),
                        const SizedBox(width: 4),
                        const Text(
                          '500 m',
                          style: TextStyle(
                            fontSize: 12,
                            color: green,
                            fontWeight: FontWeight.w600,
                          ),
                        ),

                        const Spacer(),

                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 5,
                          ),
                          decoration: BoxDecoration(
                            color: const Color(0xFFE7F8EF),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: const Text(
                            'Online',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: green,
                            ),
                          ),
                        ),
                      ],
                    ),

                    const Spacer(),

                    // TOMBOL NAVIGASI
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        onPressed: () {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('Navigasi ke Mesin SOTO #21'),
                            ),
                          );
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: green,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: const Text(
                          'Navigasi',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

class _MapMarker extends StatelessWidget {
  final bool active;

  const _MapMarker({this.active = false});

  @override
  Widget build(BuildContext context) {
    return Icon(
      Icons.location_on,
      size: active ? 32 : 26,
      color: active ? const Color(0xFF2D8A57) : const Color(0xFF1769AA),
    );
  }
}
