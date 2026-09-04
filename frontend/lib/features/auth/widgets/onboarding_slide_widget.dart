import 'package:flutter/material.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_spacing.dart';
import '../../../core/constants/app_typography.dart';

/// Data model representing an Onboarding Slide item.
class OnboardingSlideData {
  final String title;
  final String description;
  final Widget illustration;
  final String? backgroundAsset;

  const OnboardingSlideData({
    required this.title,
    required this.description,
    required this.illustration,
    this.backgroundAsset,
  });
}

/// Reusable slide item widget for Onboarding Screen.
class OnboardingSlideWidget extends StatelessWidget {
  final OnboardingSlideData data;

  const OnboardingSlideWidget({
    super.key,
    required this.data,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: AppSpacing.lg,
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          // 1. GAMBAR / ILUSTRASI
          Expanded(
            child: Center(
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 8.0),
                child: data.illustration,
              ),
            ),
          ),

          const SizedBox(height: AppSpacing.md),

          // 2. JUDUL
          Text(
            data.title,
            style: AppTypography.displayLarge.copyWith(
              color: const Color(0xFF2D6A4F),
              fontWeight: FontWeight.w700,
            ),
            textAlign: TextAlign.center,
          ),

          const SizedBox(height: AppSpacing.sm),

          // 3. DESKRIPSI
          Text(
            data.description,
            style: AppTypography.bodyLarge.copyWith(
              color: AppColors.textPrimary,
              height: 1.45,
            ),
            textAlign: TextAlign.center,
          ),

          const SizedBox(height: AppSpacing.md),
        ],
      ),
    );
  }
}