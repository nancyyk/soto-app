import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';

import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';
import 'core/services/push_notification_service.dart';
import 'core/providers/language_provider.dart';

import 'package:device_preview/device_preview.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Inisialisasi Firebase & FCM (Android only, aman jika belum dikonfigurasi)
  await PushNotificationService.instance.init();

  runApp(
    ChangeNotifierProvider(
      create: (_) => LanguageProvider(),
      child: DevicePreview(
        enabled: false,
        builder: (context) => const SotoApp(),
      ),
    ),
  );
}

/// Root application widget untuk SOTO app.
class SotoApp extends StatefulWidget {
  const SotoApp({super.key});

  @override
  State<SotoApp> createState() => _SotoAppState();
}

class _SotoAppState extends State<SotoApp> {
  @override
  void initState() {
    super.initState();
    // Setup handler navigasi FCM setelah widget pertama kali build
    // (router sudah siap di tahap ini)
    WidgetsBinding.instance.addPostFrameCallback((_) {
      PushNotificationService.instance.setupNavigationHandlers();
    });
  }

  @override
  Widget build(BuildContext context) {
    final languageProvider = context.watch<LanguageProvider>();

    return MaterialApp.router(
      title: 'SOTO',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      scaffoldMessengerKey: AppRouter.scaffoldMessengerKey,
      routerConfig: AppRouter.router,
      locale: languageProvider.locale,
      supportedLocales: LanguageProvider.supportedLocales,
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
    );
  }
}
