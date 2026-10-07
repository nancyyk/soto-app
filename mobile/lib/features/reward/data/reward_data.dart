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
    return RewardData(
      id: json['id'] as int,
      title: json['nama'] ?? 'Reward',
      description: json['deskripsi'] ?? '',
      points: json['poin'] ?? 0,
      category: json['kategori'] ?? '',
      stock: json['stok'] ?? 0,
      imagePath: json['gambar'] ?? '',
    );
  }
}