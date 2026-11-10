import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_router.dart';
import '../../../core/widgets/bottom_nav_bar.dart';
import '../../auth/data/auth_service.dart';
import '../../rfid/data/rfid_service.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  final AuthService _authService = AuthService();
  late final RfidService _rfidService;

  String _name = 'Memuat...';
  String _email = '';
  String _role = '';
  String? _rfidUid;
  bool _isUnlinking = false;

  @override
  void initState() {
    super.initState();
    _rfidService = RfidService(_authService.dio);
    _loadUser();
  }

  Future<void> _loadUser() async {
    // Tampilkan data lokal dulu agar responsif
    final cachedUser = await _authService.getUser();
    if (mounted && cachedUser != null) {
      _applyUserData(cachedUser);
    }

    // Ambil data terbaru langsung dari backend (/api/v1/me)
    final freshUser = await _authService.fetchProfile();
    if (mounted && freshUser != null) {
      _applyUserData(freshUser);
    }
  }

  void _applyUserData(Map<String, dynamic> user) {
    final rawUid = user['rfid_uid']?.toString().trim();
    setState(() {
      _name = user['nama'] ?? user['name'] ?? 'User';
      _email = user['email'] ?? '';
      _role = user['role'] ?? '';
      _rfidUid = (rawUid != null && rawUid.isNotEmpty) ? rawUid : null;
    });
  }

  /// Format UID hexadecimal agar rapi (contoh: "43C210E2" -> "43 C2 10 E2")
  String _formatRfidUid(String uid) {
    final clean = uid.replaceAll(RegExp(r'[\s:-]'), '').toUpperCase();
    if (clean.length >= 4 &&
        clean.length % 2 == 0 &&
        !uid.contains(' ') &&
        !uid.contains(':')) {
      final buffer = StringBuffer();
      for (int i = 0; i < clean.length; i += 2) {
        if (i > 0) buffer.write(' ');
        buffer.write(clean.substring(i, i + 2));
      }
      return buffer.toString();
    }
    return uid.toUpperCase();
  }

  void _showUnlinkRfidDialog() {
    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: const Text(
            'Putuskan Kartu RFID?',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: Color(0xFF222222),
            ),
          ),
          content: Text(
            'Kartu RFID (${_rfidUid != null ? _formatRfidUid(_rfidUid!) : '-'}) akan dilepas dari akun Anda. Anda dapat menghubungkan kartu baru kapan saja.',
            style: const TextStyle(fontSize: 14, color: Color(0xFF555555)),
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
                setState(() => _isUnlinking = true);
                try {
                  await _rfidService.unlink();
                  await _authService.updateLocalUser({
                    'rfid_uid': null,
                    'rfid_registered_at': null,
                  });
                  if (!mounted) return;
                  setState(() {
                    _rfidUid = null;
                    _isUnlinking = false;
                  });
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Kartu RFID berhasil dilepas dari akun'),
                      backgroundColor: Color(0xFF2D6A4F),
                    ),
                  );
                } catch (e) {
                  if (!mounted) return;
                  setState(() => _isUnlinking = false);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        e.toString().replaceFirst('Exception: ', ''),
                      ),
                      backgroundColor: Colors.red,
                    ),
                  );
                }
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
                'Putuskan',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
          ],
        );
      },
    );
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
                  builder: (_) =>
                      const Center(child: CircularProgressIndicator()),
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
    final bool isRfidConnected = _rfidUid != null && _rfidUid!.isNotEmpty;

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
        color: const Color(0xFF2D6A4F),
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
                  border: Border.all(color: const Color(0xFF2D6A4F), width: 3),
                ),
                child: const Center(
                  child: Icon(Icons.person, size: 48, color: Color(0xFF2D6A4F)),
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

              // RFID Card (Dinamis sesuai status kartu user)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: isRfidConnected
                      ? const Color(0xFF2D6A4F)
                      : const Color(0xFF4A5568),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(
                          Icons.contactless,
                          color: Colors.white,
                          size: 24,
                        ),
                        const SizedBox(width: 10),
                        const Text(
                          'RFID Card',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const Spacer(),
                        Icon(
                          isRfidConnected
                              ? Icons.check_circle
                              : Icons.error_outline,
                          color: isRfidConnected
                              ? Colors.white
                              : Colors.amberAccent,
                          size: 20,
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    Text(
                      isRfidConnected
                          ? _formatRfidUid(_rfidUid!)
                          : 'Belum Ada Kartu RFID',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: isRfidConnected ? 19 : 16,
                        fontWeight: FontWeight.w600,
                        letterSpacing: isRfidConnected ? 1.5 : 0.5,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          isRfidConnected ? 'Terhubung' : 'Belum Terhubung',
                          style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 12,
                          ),
                        ),
                        if (isRfidConnected)
                          InkWell(
                            onTap: _isUnlinking ? null : _showUnlinkRfidDialog,
                            borderRadius: BorderRadius.circular(8),
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 5,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.18),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: _isUnlinking
                                  ? const SizedBox(
                                      width: 14,
                                      height: 14,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Icon(
                                          Icons.link_off,
                                          size: 14,
                                          color: Colors.white,
                                        ),
                                        SizedBox(width: 4),
                                        Text(
                                          'Lepas Kartu',
                                          style: TextStyle(
                                            color: Colors.white,
                                            fontSize: 11,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ],
                                    ),
                            ),
                          )
                        else
                          ElevatedButton.icon(
                            onPressed: () async {
                              await context.push(AppRouter.rfid);
                              if (mounted) _loadUser();
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.white,
                              foregroundColor: const Color(0xFF2D6A4F),
                              elevation: 0,
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 6,
                              ),
                              minimumSize: Size.zero,
                              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                            ),
                            icon: const Icon(Icons.qr_code_scanner, size: 15),
                            label: const Text(
                              'Hubungkan Kartu',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 28),

              // Menu Profile
              _ProfileMenu(
                icon: Icons.person_outline,
                title: 'Edit Profile',
                onTap: () async {
                  await context.push(AppRouter.editProfile);
                  if (mounted) _loadUser();
                },
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
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 3),
        leading: Icon(icon, color: iconColor, size: 22),
        title: Text(
          title,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: textColor,
          ),
        ),
        trailing: const Icon(Icons.chevron_right, color: Color(0xFFAAAAAA)),
      ),
    );
  }
}
