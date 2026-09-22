import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/bottom_nav_bar.dart';
import '../../auth/data/auth_service.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  final AuthService _authService = AuthService();

  String _name = 'Memuat...';
  String _email = '';
  String _role = '';

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await _authService.getUser();
    if (!mounted) return;
    setState(() {
      _name = user?['nama'] ?? user?['name'] ?? 'User';
      _email = user?['email'] ?? '';
      _role = user?['role'] ?? '';
    });
  }

  void _showLogoutDialog() {
    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: const Text(
            'Konfirmasi Logout',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: Color(0xFF222222),
            ),
          ),
          content: const Text(
            'Apakah Anda yakin ingin keluar?',
            style: TextStyle(fontSize: 14, color: Color(0xFF555555)),
          ),
          actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text(
                'Batal',
                style: TextStyle(
                  color: Color(0xFF777777),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            ElevatedButton(
              onPressed: () async {
                Navigator.pop(dialogContext);

                // Tampilkan loading
                showDialog(
                  context: context,
                  barrierDismissible: false,
                  builder: (_) => const Center(
                    child: CircularProgressIndicator(),
                  ),
                );

                // Panggil API logout + hapus token lokal
                await _authService.logout();

                if (!mounted) return;

                // Tutup loading
                Navigator.of(context, rootNavigator: true).pop();

                // Kembali ke halaman login
                context.go(AppRouter.login);
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
                foregroundColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: const Text(
                'Logout',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF7F9F8),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        title: const Text(
          'Profile',
          style: TextStyle(
            color: Color(0xFF222222),
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadUser,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 30, 20, 24),
          child: Column(
            children: [
              // Foto profil
              Container(
                width: 88,
                height: 88,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: const Color(0xFF2D6A4F),
                    width: 3,
                  ),
                ),
                child: ClipOval(
                  child: Image.asset(
                    'assets/images/profile.png',
                    width: 88,
                    height: 88,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => const Icon(
                      Icons.person,
                      size: 48,
                      color: Color(0xFF2D6A4F),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 12),

              // Nama
              Text(
                _name,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF222222),
                ),
              ),
              const SizedBox(height: 5),

              // Email
              if (_email.isNotEmpty)
                Text(
                  _email,
                  style: const TextStyle(
                    fontSize: 13,
                    color: Color(0xFF777777),
                  ),
                ),

              // Role
              if (_role.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  'Role: ${_role.toUpperCase()}',
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF2D6A4F),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],

              const SizedBox(height: 28),

              // RFID Card
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: const Color(0xFF2D6A4F),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    Row(
                      children: [
                        Icon(
                          Icons.contactless,
                          color: Colors.white,
                          size: 24,
                        ),
                        SizedBox(width: 10),
                        Text(
                          'RFID Card',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        Spacer(),
                        Icon(
                          Icons.check_circle,
                          color: Colors.white,
                          size: 20,
                        ),
                      ],
                    ),
                    SizedBox(height: 18),
                    Text(
                      '1234 4567 6789 9876',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 19,
                        fontWeight: FontWeight.w600,
                        letterSpacing: 1.5,
                      ),
                    ),
                    SizedBox(height: 8),
                    Text(
                      'Terhubung',
                      style: TextStyle(
                        color: Colors.white70,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 28),

              // Menu Profile
              _ProfileMenu(
                icon: Icons.person_outline,
                title: 'Edit Profile',
                onTap: () => context.push(AppRouter.editProfile),
              ),
              _ProfileMenu(
                icon: Icons.lock_outline,
                title: 'Ganti Password',
                onTap: () => context.push(AppRouter.changePassword),
              ),
              _ProfileMenu(
                icon: Icons.history,
                title: 'Riwayat Saya',
                onTap: () => context.go(AppRouter.history),
              ),
              _ProfileMenu(
                icon: Icons.settings_outlined,
                title: 'Pengaturan',
                onTap: () => context.push(AppRouter.settings),
              ),
              _ProfileMenu(
                icon: Icons.info_outline,
                title: 'Tentang Aplikasi',
                onTap: () => context.push(AppRouter.about),
              ),
              _ProfileMenu(
                icon: Icons.logout,
                title: 'Logout',
                textColor: Colors.red,
                iconColor: Colors.red,
                onTap: _showLogoutDialog,
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

class _ProfileMenu extends StatelessWidget {
  final IconData icon;
  final String title;
  final VoidCallback onTap;
  final Color iconColor;
  final Color textColor;

  const _ProfileMenu({
    required this.icon,
    required this.title,
    required this.onTap,
    this.iconColor = const Color(0xFF2D6A4F),
    this.textColor = const Color(0xFF333333),
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
      ),
      child: ListTile(
        onTap: onTap,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 3),
        leading: Icon(icon, color: iconColor, size: 22),
        title: Text(
          title,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: textColor,
          ),
        ),
        trailing: const Icon(
          Icons.chevron_right,
          color: Color(0xFFAAAAAA),
        ),
      ),
    );
  }
}