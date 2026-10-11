import 'package:flutter/foundation.dart';
import '../../auth/data/auth_service.dart';

class RewardData {
  final int id;
  final String title;
  final String description;
  final int points;
  final String category;
  final String imagePath;
  final int stock;

  const RewardData({
    required this.id,
    required this.title,
    required this.description,
    required this.points,
    required this.category,
    required this.imagePath,
    required this.stock,
  });

  factory RewardData.fromJson(Map<String, dynamic> json) {
    final rawImage = (json['gambar_url'] ?? json['gambar'] ?? '').toString();

    return RewardData(
      id: json['id'] as int,
      title: json['nama'] ?? 'Reward',
      description: json['deskripsi'] ?? '',
      points: json['poin'] ?? 0,
      category: json['kategori'] ?? '',
      stock: json['stok'] ?? 0,
      imagePath: resolveImagePath(rawImage),
    );
  }

  static String resolveImagePath(String raw) {
    final trimmed = raw.trim();
    if (trimmed.isEmpty) return '';

    // Jika berupa asset lokal Flutter
    if (trimmed.startsWith('assets/')) {
      return trimmed;
    }

    // Jika sudah berupa URL lengkap http / https
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
      final uri = Uri.tryParse(trimmed);
      if (uri != null) {
        // Jika URL membungkus asset lokal
        if (uri.path.contains('assets/images/')) {
          final assetIndex = uri.path.indexOf('assets/');
          return uri.path.substring(assetIndex);
        }

        final baseUri = Uri.tryParse(AuthService.serverBaseUrl);

        // Arahkan ke endpoint /api/v1/storage/ jika masih /storage/ agar melewati CORS middleware
        String path = uri.path;
        if (path.startsWith('/storage/')) {
          path = '/api/v1$path';
        }

        // Sinkronisasi host sesuai platform (Web / HP Fisik / Emulator)
        if (baseUri != null && baseUri.host.isNotEmpty) {
          final shouldReplaceHost = uri.host == 'localhost' ||
              uri.host == '127.0.0.1' ||
              uri.host == '192.168.50.29' ||
              (kIsWeb && uri.host != baseUri.host);

          if (shouldReplaceHost) {
            return uri.replace(
              scheme: baseUri.scheme,
              host: baseUri.host,
              port: baseUri.hasPort ? baseUri.port : (uri.hasPort ? uri.port : null),
              path: path,
            ).toString();
          }
        }

        if (path != uri.path) {
          return uri.replace(path: path).toString();
        }
      }
      return trimmed;
    }

    // Jika path relatif backend (misal: 'rewards/xxx.png' atau '/storage/rewards/xxx.png')
    final base = AuthService.serverBaseUrl;
    final clean = trimmed.startsWith('/') ? trimmed.substring(1) : trimmed;
    if (clean.startsWith('api/v1/storage/')) {
      return '$base/$clean';
    }
    if (clean.startsWith('storage/')) {
      return '$base/api/v1/$clean';
    }
    return '$base/api/v1/storage/$clean';
  }
}