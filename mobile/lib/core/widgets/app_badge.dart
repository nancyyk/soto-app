import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_spacing.dart';
import '../constants/app_typography.dart';

/// Reusable pill status badge or tag component.
class AppBadge extends StatelessWidget {
  final String label;
  final Color backgroundColor;
  final Color textColor;
  final IconData? icon;

  const AppBadge({
    super.key,
    required this.label,
    this.backgroundColor = AppColors.primaryContainer,
    this.textColor = AppColors.primaryDark,
    this.icon,
  });

  factory AppBadge.success({required String label}) => AppBadge(
        label: label,
        backgroundColor: const Color(0xFFD1FAE5),
        textColor: AppColors.primaryDark,
        icon: Icons.check_circle_outline,
      );

  factory AppBadge.warning({required String label}) => AppBadge(
        label: label,
        backgroundColor: AppColors.accentLight,
        textColor: AppColors.warning,
        icon: Icons.warning_amber_outlined,
      );

  factory AppBadge.error({required String label}) => AppBadge(
        label: label,
        backgroundColor: const Color(0xFFFEE2E2),
        textColor: AppColors.error,
        icon: Icons.error_outline,
      );

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: AppRadius.borderPill,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 14, color: textColor),
            const SizedBox(width: 4),
          ],
          Text(
            label,
            style: AppTypography.labelSmall.copyWith(
              color: textColor,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
