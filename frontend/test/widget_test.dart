import 'package:flutter_test/flutter_test.dart';
import 'package:soto_app/main.dart';

void main() {
  testWidgets('App smoke test', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(const SotoApp());

    // Verify that SOTO title is present
    expect(find.text('SOTO'), findsOneWidget);
  });
}
