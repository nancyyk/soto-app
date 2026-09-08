import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_colors.dart';
import '../widgets/onboarding_indicator_widget.dart';

class OnboardingPage extends StatefulWidget {
  const OnboardingPage({super.key});

  @override
  State<OnboardingPage> createState() => _OnboardingPageState();
}

class _OnboardingPageState extends State<OnboardingPage> {
  final PageController _pageController = PageController();
  int _currentIndex = 0;

  final List<_OnboardingData> _slides = const [
    _OnboardingData(
      title: 'Buang Botol\nJadi Poin',
      description:
          'Masukkan botol plastik ke mesin SOTO dan dapatkan poin secara otomatis.',
      image: 'assets/images/botol1.png',
    ),
    _OnboardingData(
      title: 'Pantau\nPoin Anda',
      description:
          'Lihat riwayat, poin dan reward kapan saja di genggaman.',
      image: 'assets/images/onboarding2.png',
    ),
    _OnboardingData(
      title: 'Dukung\nLingkungan',
      description:
          'Setiap botol yang kamu daur ulang, membantu bumi lebih bersih.',
      image: 'assets/images/onboarding3.png',
    ),
  ];

  void _next() {
    if (_currentIndex < _slides.length - 1) {
      _pageController.nextPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    } else {
      context.go('/login');
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isLast = _currentIndex == 2;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Stack(
        fit: StackFit.expand,
        children: [
          Image.asset(
            'assets/images/background-2.jpg',
            fit: BoxFit.cover,
          ),

          SafeArea(
            child: Column(
              children: [
                /// Area slide
                Expanded(
                  child: PageView.builder(
                    controller: _pageController,
                    itemCount: _slides.length,
                    onPageChanged: (index) {
                      setState(() => _currentIndex = index);
                    },
                    itemBuilder: (_, index) {
                      return _OnboardingSlide(data: _slides[index]);
                    },
                  ),
                ),

                Transform.translate(
                  offset: const Offset(0, -90), 
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      OnboardingIndicatorWidget(
                        count: 3,
                        currentIndex: _currentIndex,
                      ),

                      const SizedBox(height: 14),

                      SizedBox(
                        width: 170,
                        height: 46,
                        child: ElevatedButton(
                          onPressed: _next,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF2D6A4F),
                            elevation: 0,
                            shape: const StadiumBorder(),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                isLast ? 'Mulai sekarang' : 'Selanjutnya',
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: Colors.white,
                                ),
                              ),
                              const SizedBox(width: 6),
                              const Icon(
                                Icons.arrow_forward_ios_rounded,
                                size: 14,
                                color: Colors.white,
                              )
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 12),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _OnboardingSlide extends StatelessWidget {
  final _OnboardingData data;

  const _OnboardingSlide({required this.data});

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, c) {
        final h = c.maxHeight;

        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: 28),
          child: Column(
            children: [
              SizedBox(height: h * 0.11),

              Text(
                data.title,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Color(0xFF2D6A4F),
                  fontSize: 31,
                  fontWeight: FontWeight.w700,
                  height: 1.08,
                ),
              ),

              const SizedBox(height: 10),

              Text(
                data.description,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Color(0xFF4B5563),
                  fontSize: 14,
                  height: 1.45,
                ),
              ),

              SizedBox(height: h * 0.035),

              Image.asset(
                data.image,
                height: h * 0.47,
                fit: BoxFit.contain,
              ),
            ],
          ),
        );
      },
    );
  }
}

class _OnboardingData {
  final String title;
  final String description;
  final String image;

  const _OnboardingData({
    required this.title,
    required this.description,
    required this.image,
  });
}