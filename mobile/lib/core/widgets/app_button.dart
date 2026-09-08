import 'package:flutter/material.dart';
import '../constants/app_colors.dart';

enum AppButtonVariant { primary, secondary, outline, text }

/// Reusable primary / secondary / outline button component.
class AppButton extends StatelessWidget {
  final String text;
  final VoidCallback? onPressed;
  final AppButtonVariant variant;
  final bool isLoading;
  final bool isFullWidth;
  final IconData? icon;
  final double? height;
  final Color? backgroundColor;
  final Color? textColor;

  const AppButton({
    super.key,
    required this.text,
    this.onPressed,
    this.variant = AppButtonVariant.primary,
    this.isLoading = false,
    this.isFullWidth = true,
    this.icon,
    this.height = 52.0,
    this.backgroundColor,
    this.textColor,
  });

  @override
  Widget build(BuildContext context) {
    final effectiveHeight = height ?? 52.0;

    Widget child = isLoading
        ? SizedBox(
            height: 20,
            width: 20,
            child: CircularProgressIndicator(
              strokeWidth: 2.5,
              valueColor: AlwaysStoppedAnimation<Color>(
                variant == AppButtonVariant.outline || variant == AppButtonVariant.text
                    ? AppColors.primary
                    : AppColors.textWhite,
              ),
            ),
          )
        : Row(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (icon != null) ...[
                Icon(icon, size: 20),
                const SizedBox(width: 8),
              ],
              Text(text),
            ],
          );

    Widget button;

    switch (variant) {
      case AppButtonVariant.primary:
        button = ElevatedButton(
          onPressed: isLoading ? null : onPressed,
          style: backgroundColor != null || textColor != null
              ? ElevatedButton.styleFrom(
                  backgroundColor: backgroundColor,
                  foregroundColor: textColor,
                )
              : null,
          child: child,
        );
        break;

      case AppButtonVariant.secondary:
        button = ElevatedButton(
          onPressed: isLoading ? null : onPressed,
          style: ElevatedButton.styleFrom(
            backgroundColor: backgroundColor ?? AppColors.primaryContainer,
            foregroundColor: textColor ?? AppColors.primaryDark,
            elevation: 0,
          ),
          child: child,
        );
        break;

      case AppButtonVariant.outline:
        button = OutlinedButton(
          onPressed: isLoading ? null : onPressed,
          style: backgroundColor != null || textColor != null
              ? OutlinedButton.styleFrom(
                  backgroundColor: backgroundColor,
                  foregroundColor: textColor,
                  side: BorderSide(color: textColor ?? AppColors.primary),
                )
              : null,
          child: child,
        );
        break;

      case AppButtonVariant.text:
        button = TextButton(
          onPressed: isLoading ? null : onPressed,
          style: textColor != null
              ? TextButton.styleFrom(foregroundColor: textColor)
              : null,
          child: child,
        );
        break;
    }

    return SizedBox(
      width: isFullWidth ? double.infinity : null,
      height: effectiveHeight,
      child: button,
    );
  }
}
