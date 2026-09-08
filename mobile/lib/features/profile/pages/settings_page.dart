import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class SettingsPage extends StatefulWidget {
  const SettingsPage({super.key});

  @override
  State<SettingsPage> createState() => _SettingsPageState();
}

class _SettingsPageState extends State<SettingsPage> {
  bool _pushNotifications = true;
  bool _emailNotifications = false;
  String _selectedLanguage = 'Bahasa Indonesia';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF7F9F8),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios_new,
            color: Color(0xFF222222),
            size: 20,
          ),
          onPressed: () => context.pop(),
        ),
        title: const Text(
          'Pengaturan',
          style: TextStyle(
            color: Color(0xFF222222),
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 26, 20, 30),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'NOTIFIKASI',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF2D6A4F),
                  letterSpacing: 0.8,
                ),
              ),
              const SizedBox(height: 10),
              Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFE5E5E5)),
                ),
                child: Column(
                  children: [
                    SwitchListTile(
                      activeTrackColor: const Color(0xFF2D6A4F),
                      title: const Text(
                        'Notifikasi Push',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF222222),
                        ),
                      ),
                      subtitle: const Text(
                        'Dapatkan notifikasi poin dan promo',
                        style: TextStyle(
                          fontSize: 12,
                          color: Color(0xFF777777),
                        ),
                      ),
                      value: _pushNotifications,
                      onChanged: (val) {
                        setState(() {
                          _pushNotifications = val;
                        });
                      },
                    ),
                    const Divider(height: 1, color: Color(0xFFEDEDED)),
                    SwitchListTile(
                      activeTrackColor: const Color(0xFF2D6A4F),
                      title: const Text(
                        'Notifikasi Email',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF222222),
                        ),
                      ),
                      subtitle: const Text(
                        'Terima laporan bulanan via email',
                        style: TextStyle(
                          fontSize: 12,
                          color: Color(0xFF777777),
                        ),
                      ),
                      value: _emailNotifications,
                      onChanged: (val) {
                        setState(() {
                          _emailNotifications = val;
                        });
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              const Text(
                'UMUM',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF2D6A4F),
                  letterSpacing: 0.8,
                ),
              ),
              const SizedBox(height: 10),
              Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFE5E5E5)),
                ),
                child: ListTile(
                  leading: const Icon(
                    Icons.language,
                    color: Color(0xFF2D6A4F),
                    size: 22,
                  ),
                  title: const Text(
                    'Bahasa',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      color: Color(0xFF222222),
                    ),
                  ),
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        _selectedLanguage,
                        style: const TextStyle(
                          fontSize: 13,
                          color: Color(0xFF777777),
                        ),
                      ),
                      const SizedBox(width: 4),
                      const Icon(Icons.chevron_right, color: Color(0xFFAAAAAA)),
                    ],
                  ),
                  onTap: () {
                    setState(() {
                      _selectedLanguage =
                          _selectedLanguage == 'Bahasa Indonesia'
                          ? 'English'
                          : 'Bahasa Indonesia';
                    });
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
