import 'package:flutter/material.dart';

/// App color palette based on SOTO Figma design specifications.
class AppColors {
  AppColors._();

  // Primary Brand Colors
  static const Color primary = Color(0xFF10B981); // Emerald Eco Green
  static const Color primaryDark = Color(0xFF047857); // Deep Emerald
  static const Color primaryLight = Color(0xFFD1FAE5); // Soft Mint
  static const Color primaryContainer = Color(0xFFE6F4EA); // Very Light Green

  // Secondary Brand Colors
  static const Color secondary = Color(0xFF0EA5E9); // Ocean Blue / Teal
  static const Color secondaryDark = Color(0xFF0284C7);
  static const Color secondaryLight = Color(0xFFE0F2FE);

  // Accent Colors
  static const Color accent = Color(0xFFF59E0B); // Amber / Reward Gold
  static const Color accentLight = Color(0xFFFEF3C7);

  // Neutral Background & Surface
  static const Color background = Color(0xFFF8FAFC);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color cardSurface = Color(0xFFFFFFFF);
  static const Color inputFill = Color(0xFFF1F5F9);

  // Text Colors
  static const Color textPrimary = Color(0xFF0F172A);
  static const Color textSecondary = Color(0xFF64748B);
  static const Color textMuted = Color(0xFF94A3B8);
  static const Color textWhite = Color(0xFFFFFFFF);

  // Border & Divider
  static const Color border = Color(0xFFE2E8F0);
  static const Color borderDark = Color(0xFFCBD5E1);
  static const Color divider = Color(0xFFF1F5F9);

  // Status & Feedback Colors
  static const Color success = Color(0xFF10B981);
  static const Color warning = Color(0xFFF59E0B);
  static const Color error = Color(0xFFEF4444);
  static const Color info = Color(0xFF3B82F6);

  // Dark Mode Palette
  static const Color darkBackground = Color(0xFF0F172A);
  static const Color darkSurface = Color(0xFF1E293B);
  static const Color darkCardSurface = Color(0xFF334155);
  static const Color darkTextPrimary = Color(0xFFF8FAFC);
  static const Color darkTextSecondary = Color(0xFF94A3B8);
  static const Color darkBorder = Color(0xFF334155);
}
