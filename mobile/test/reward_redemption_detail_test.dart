import 'package:device_preview/device_preview.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:soto_app/core/router/app_router.dart';
import 'package:soto_app/core/theme/app_theme.dart';
import 'package:soto_app/features/reward/data/reward_data.dart';
import 'package:soto_app/features/reward/data/reward_redemption_data.dart';
import 'package:soto_app/features/reward/pages/reward_redemption_detail_page.dart';

void main() {
  testWidgets(
      'RewardRedemptionDetailPage renders voucher code, copy button, and back navigation without layout or mouse_tracker errors',
      (WidgetTester tester) async {
    const voucherReward = RewardData(
      id: 1,
      code: 'VCR-10K',
      title: 'Voucher Belanja Rp 10.000',
      description: 'Voucher belanja minimarket',
      points: 100,
      category: 'Voucher',
      imagePath: '',
      stock: 10,
    );

    final redemption = RewardRedemptionData(
      id: 5,
      userId: 1,
      rewardId: 1,
      points: 100,
      status: 'selesai',
      voucherCode: 'SOTO-VCR10K-ABC123',
      createdAt: DateTime(2026, 11, 13, 10, 0),
      reward: voucherReward,
    );

    final router = GoRouter(
      initialLocation: '/list',
      routes: [
        GoRoute(
          path: '/list',
          builder: (context, state) => Scaffold(
            body: Center(
              child: InkWell(
                onTap: () {
                  context.push(
                    AppRouter.rewardRedemptionDetail,
                    extra: redemption,
                  );
                },
                child: const Padding(
                  padding: EdgeInsets.all(20),
                  child: Text('Lihat Detail Status'),
                ),
              ),
            ),
          ),
        ),
        GoRoute(
          path: AppRouter.rewardRedemptionDetail,
          builder: (context, state) {
            final extra = state.extra;
            if (extra is! RewardRedemptionData) {
              return const Scaffold(body: Text('No extra'));
            }
            return RewardRedemptionDetailPage(redemption: extra);
          },
        ),
      ],
    );

    await tester.pumpWidget(
      DevicePreview(
        enabled: true,
        builder: (context) => MaterialApp.router(
          theme: AppTheme.lightTheme,
          routerConfig: router,
        ),
      ),
    );
    await tester.pumpAndSettle();

    final gesture = await tester.createGesture(kind: PointerDeviceKind.mouse);
    await gesture.addPointer(location: Offset.zero);
    addTearDown(gesture.removePointer);

    final btnFinder = find.text('Lihat Detail Status');
    await gesture.moveTo(tester.getCenter(btnFinder));
    await tester.pump();
    await tester.tap(btnFinder);
    await tester.pumpAndSettle();

    expect(find.text('Status Penukaran'), findsOneWidget);
    expect(find.text('KODE VOUCHER BELANJA'), findsOneWidget);
    // Voucher code appears in both the voucher card and Ringkasan Transaksi
    expect(find.text('SOTO-VCR10K-ABC123'), findsNWidgets(2));

    // Hover and tap Salin button
    final copyBtn = find.text('Salin');
    await gesture.moveTo(tester.getCenter(copyBtn));
    await tester.pump();
    await tester.tap(copyBtn);
    await tester.pump();
    expect(find.text('Kode voucher berhasil disalin!'), findsOneWidget);

    // Move mouse and tap back button
    await gesture.moveTo(tester.getCenter(find.byIcon(Icons.arrow_back_ios_new)));
    await tester.pump();
    await tester.tap(find.byIcon(Icons.arrow_back_ios_new));
    await tester.pumpAndSettle();
    expect(find.text('Lihat Detail Status'), findsOneWidget);
  });
}
