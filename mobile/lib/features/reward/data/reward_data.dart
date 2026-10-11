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

    // Jika sudah berupa URL lengkap http / https
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
      return trimmed;
    }

    // Jika berupa asset lokal Flutter
    if (trimmed.startsWith('assets/')) {
      return trimmed;
    }

    // Jika path relatif backend (misal: 'rewards/xxx.png' atau '/storage/rewards/xxx.png')
    final base = AuthService.serverBaseUrl;
    final clean = trimmed.startsWith('/') ? trimmed.substring(1) : trimmed;
    if (clean.startsWith('storage/')) {
      return '$base/$clean';
    }
    return '$base/storage/$clean';
  }
}