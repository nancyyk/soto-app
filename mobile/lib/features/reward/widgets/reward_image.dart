import 'package:flutter/material.dart';

class RewardImage extends StatelessWidget {
  final String imagePath;
  final BoxFit fit;
  final double? width;
  final double? height;
  final Widget? fallback;

  const RewardImage({
    super.key,
    required this.imagePath,
    this.fit = BoxFit.contain,
    this.width,
    this.height,
    this.fallback,
  });

  @override
  Widget build(BuildContext context) {
    final trimmed = imagePath.trim();

    if (trimmed.isEmpty) {
      return fallback ?? _defaultFallback();
    }

    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
      return Image.network(
        trimmed,
        width: width,
        height: height,
        fit: fit,
        filterQuality: FilterQuality.high,
        errorBuilder: (context, error, stackTrace) {
          return fallback ?? _defaultFallback();
        },
        loadingBuilder: (context, child, loadingProgress) {
          if (loadingProgress == null) return child;
          return Center(
            child: SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: const Color(0xFF2D6A4F),
                value: loadingProgress.expectedTotalBytes != null
                    ? loadingProgress.cumulativeBytesLoaded /
                        loadingProgress.expectedTotalBytes!
                    : null,
              ),
            ),
          );
        },
      );
    }

    if (trimmed.startsWith('assets/')) {
      return Image.asset(
        trimmed,
        width: width,
        height: height,
        fit: fit,
        filterQuality: FilterQuality.high,
        errorBuilder: (context, error, stackTrace) {
          return fallback ?? _defaultFallback();
        },
      );
    }

    return fallback ?? _defaultFallback();
  }

  Widget _defaultFallback() {
    return const Center(
      child: Icon(
        Icons.card_giftcard,
        color: Color(0xFF2D6A4F),
        size: 28,
      ),
    );
  }
}
