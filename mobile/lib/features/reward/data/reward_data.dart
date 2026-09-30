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
      title: json['name'] ?? 'Reward',
      description: json['description'] ?? '',
      points: json['points_required'] ?? 0,
      category: 'Tersedia', // Or add category to backend later
      stock: json['stock'] ?? 0,
      imagePath: json['image_url'] ?? 'assets/images/voucher1.png',
    );
  }
}