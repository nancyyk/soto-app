import 'package:flutter/material.dart';
import '../constants/app_colors.dart';

/// Standardized loading spinner component.
class AppLoadingIndicator extends StatelessWidget {
  final double size;
  final Color color;

  const AppLoadingIndicator({
    super.key,
    this.size = 36.0,
    this.color = AppColors.primary,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: SizedBox(
        width: size,
        height: size,
        child: CircularProgressIndicator(
          strokeWidth: 3,
          valueColor: AlwaysStoppedAnimation<Color>(color),
        ),
      ),
    );
  }
}
