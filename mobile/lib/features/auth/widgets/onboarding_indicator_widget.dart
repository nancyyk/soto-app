import 'package:flutter/material.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_spacing.dart';

/// Animated page dot indicator for Onboarding screens.
class OnboardingIndicatorWidget extends StatelessWidget {
  final int count;
  final int currentIndex;

  const OnboardingIndicatorWidget({
    super.key,
    required this.count,
    required this.currentIndex,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(count, (index) {
        final isSelected = index == currentIndex;
        return AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeInOut,
          margin: const EdgeInsets.symmetric(horizontal: AppSpacing.xs),
          height: 8.0,
          width: isSelected ? 24.0 : 8.0,
          decoration: BoxDecoration(
            color: isSelected ? const Color(0xFF2D6A4F) : AppColors.borderDark,
            borderRadius: AppRadius.borderPill,
          ),
        );
      }),
    );
  }
}
