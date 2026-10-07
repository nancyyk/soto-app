import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/widgets/bottom_nav_bar.dart';
import '../auth/data/auth_service.dart';

class NotificationPage extends StatefulWidget {
  const NotificationPage({super.key});

  static const Color green = Color(0xFF2D6A4F);
  static const Color lightGreen = Color(0xFFE8F7F0);

  @override
  State<NotificationPage> createState() => _NotificationPageState();
}

class _NotificationPageState extends State<NotificationPage> {
  final AuthService _authService = AuthService();
  bool _isLoading = true;
  String? _error;
  List<_NotificationItem> _notifications = [];

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  Future<void> _fetchNotifications() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final response = await _authService.dio.get('/notifications');
      if (response.statusCode == 200 && response.data is List) {
        final rawList = response.data as List;
        final parsed = rawList.map((item) {
          if (item is Map<String, dynamic>) {
            return _parseApiNotification(item);
          }
          return _parseApiNotification(Map<String, dynamic>.from(item as Map));
        }).toList();

        setState(() {
          _notifications = parsed;
          _isLoading = false;
        });
      } else {
        setState(() {
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Gagal memuat notifikasi';
        _isLoading = false;
      });
    }
  }

  _NotificationItem _parseApiNotification(Map<String, dynamic> item) {
    final title = item['title']?.toString() ?? 'Notifikasi';
    final description = item['description']?.toString() ?? '';
    final time = _formatTime(item['created_at']?.toString());
    final dataMap = item['data'] is Map<String, dynamic>
        ? item['data'] as Map<String, dynamic>
        : <String, dynamic>{};

    final isWarning = dataMap['warning'] == true ||
        dataMap['icon'] == 'warning' ||
        title.toLowerCase().contains('penuh') ||
        title.toLowerCase().contains('habis') ||
        title.toLowerCase().contains('menipis');

    IconData iconData = Icons.card_giftcard_outlined;
    if (isWarning) {
      iconData = Icons.warning_amber_rounded;
    } else if (dataMap['icon'] == 'check' || title.toLowerCase().contains('berhasil')) {
      iconData = Icons.check_circle_outline;
    } else if (dataMap['icon'] == 'add' || title.toLowerCase().contains('poin')) {
      iconData = Icons.add;
    }

    return _NotificationItem(
      icon: iconData,
      title: title,
      description: description,
      time: time,
      warning: isWarning,
    );
  }

  String _formatTime(String? rawDate) {
    if (rawDate == null || rawDate.isEmpty) return '-';
    try {
      final dateTime = DateTime.parse(rawDate).toLocal();
      final difference = DateTime.now().difference(dateTime);

      if (difference.inSeconds < 60) {
        return 'Baru saja';
      } else if (difference.inMinutes < 60) {
        return '${difference.inMinutes} menit lalu';
      } else if (difference.inHours < 24) {
        return '${difference.inHours} jam lalu';
      } else if (difference.inDays < 30) {
        return '${difference.inDays} hari lalu';
      } else {
        return '${dateTime.day}/${dateTime.month}/${dateTime.year}';
      }
    } catch (_) {
      return rawDate;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          onPressed: () => context.pop(),
          icon: const Icon(
            Icons.arrow_back_ios_new,
            color: Colors.black,
            size: 20,
          ),
        ),
        title: const Text(
          'Notifikasi',
          style: TextStyle(
            color: Colors.black,
            fontSize: 16,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(
                color: NotificationPage.green,
              ),
            )
          : RefreshIndicator(
              onRefresh: _fetchNotifications,
              color: NotificationPage.green,
              child: _notifications.isEmpty
                  ? ListView(
                      padding: const EdgeInsets.fromLTRB(18, 26, 18, 24),
                      children: const [
                        SizedBox(height: 100),
                        Center(
                          child: Text(
                            'Belum ada notifikasi',
                            style: TextStyle(
                              color: Colors.black54,
                              fontSize: 14,
                            ),
                          ),
                        ),
                      ],
                    )
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(18, 26, 18, 24),
                      itemCount: _notifications.length,
                      separatorBuilder: (context, index) =>
                          const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        final item = _notifications[index];

                        return Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            border: Border.all(color: const Color(0xFFE0E0E0)),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.center,
                            children: [
                              Container(
                                width: 38,
                                height: 38,
                                decoration: BoxDecoration(
                                  color: NotificationPage.lightGreen,
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Icon(
                                  item.icon,
                                  size: 22,
                                  color: item.warning
                                      ? const Color(0xFFFFA726)
                                      : NotificationPage.green,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      item.title,
                                      style: const TextStyle(
                                        fontSize: 13,
                                        fontWeight: FontWeight.w700,
                                        color: Colors.black,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      item.description,
                                      style: const TextStyle(
                                        fontSize: 11,
                                        height: 1.3,
                                        color: Colors.black87,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    Text(
                                      item.time,
                                      style: const TextStyle(
                                        fontSize: 10,
                                        color: Colors.black54,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
            ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

class _NotificationItem {
  final IconData icon;
  final String title;
  final String description;
  final String time;
  final bool warning;

  const _NotificationItem({
    required this.icon,
    required this.title,
    required this.description,
    required this.time,
    this.warning = false,
  });
}
