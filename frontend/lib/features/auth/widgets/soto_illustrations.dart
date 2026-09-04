import 'package:flutter/material.dart';
import '../../../core/constants/app_colors.dart';

/// Custom high-quality vector illustrations for SOTO logo, splash, and onboarding slides.
class SotoIllustrations {
  SotoIllustrations._();

  /// SOTO App Brand Logo Widget (Used in Splash and Headers)
  static Widget logo({double size = 100}) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: AppColors.primaryContainer,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.2),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Center(
        child: Stack(
          alignment: Alignment.center,
          children: [
            Icon(
              Icons.recycling_rounded,
              size: size * 0.55,
              color: AppColors.primary,
            ),
            Positioned(
              right: size * 0.22,
              bottom: size * 0.22,
              child: Container(
                padding: EdgeInsets.all(size * 0.04),
                decoration: const BoxDecoration(
                  color: AppColors.accent,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.opacity_rounded,
                  size: size * 0.2,
                  color: Colors.white,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Onboarding Slide 1 Illustration: Oil Recycling & Collection
  static Widget onboardingOilRecycling({double width = 280, double height = 280}) {
    return Container(
      width: width,
      height: height,
      decoration: const BoxDecoration(
        gradient: RadialGradient(
          colors: [
            AppColors.primaryContainer,
            AppColors.background,
          ],
          radius: 0.85,
        ),
        shape: BoxShape.circle,
      ),
      child: Center(
        child: Stack(
          alignment: Alignment.center,
          children: [
            // Background decor circles
            Container(
              width: width * 0.75,
              height: height * 0.75,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.primary.withValues(alpha: 0.2), width: 2),
              ),
            ),
            // Center Oil Bottle & Recycling Icon
            Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 16,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.opacity,
                    size: 64,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: AppColors.primary,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.autorenew_rounded, color: Colors.white, size: 16),
                          SizedBox(width: 4),
                          Text(
                            "Collect Oil",
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  /// Onboarding Slide 2 Illustration: Smart Machine Finder & Map
  static Widget onboardingSmartMachine({double width = 280, double height = 280}) {
    return Container(
      width: width,
      height: height,
      decoration: const BoxDecoration(
        gradient: RadialGradient(
          colors: [
            AppColors.secondaryLight,
            AppColors.background,
          ],
          radius: 0.85,
        ),
        shape: BoxShape.circle,
      ),
      child: Center(
        child: Stack(
          alignment: Alignment.center,
          children: [
            // Outer ring
            Container(
              width: width * 0.78,
              height: height * 0.78,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.secondary.withValues(alpha: 0.25), width: 2),
              ),
            ),
            // Machine Icon & Map pin overlay
            Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 16,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.store_mall_directory_rounded,
                    size: 64,
                    color: AppColors.secondary,
                  ),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppColors.secondary,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.location_on_rounded, color: Colors.white, size: 16),
                      SizedBox(width: 4),
                      Text(
                        "SOTO Vending Machine",
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  /// Onboarding Slide 3 Illustration: Points & Eco Rewards
  static Widget onboardingRewards({double width = 280, double height = 280}) {
    return Container(
      width: width,
      height: height,
      decoration: const BoxDecoration(
        gradient: RadialGradient(
          colors: [
            AppColors.accentLight,
            AppColors.background,
          ],
          radius: 0.85,
        ),
        shape: BoxShape.circle,
      ),
      child: Center(
        child: Stack(
          alignment: Alignment.center,
          children: [
            Container(
              width: width * 0.78,
              height: height * 0.78,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.accent.withValues(alpha: 0.3), width: 2),
              ),
            ),
            Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 16,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.stars_rounded,
                    size: 64,
                    color: AppColors.accent,
                  ),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppColors.accent,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 16),
                      SizedBox(width: 4),
                      Text(
                        "Cashback & Rewards",
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
