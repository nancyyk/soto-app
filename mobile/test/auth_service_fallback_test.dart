import 'package:flutter_test/flutter_test.dart';
import 'package:soto_app/features/auth/data/auth_service.dart';

void main() {
  setUp(() {
    AuthService.switchToAzure();
  });

  tearDown(() {
    AuthService.switchToAzure();
  });

  group('AuthService baseUrl and serverBaseUrl fallback', () {
    test('Default baseUrl points to Azure VM when isAzureDown is false', () {
      expect(AuthService.isAzureDown, isFalse);
      expect(AuthService.baseUrl, equals('http://20.70.176.117/api/v1'));
      expect(AuthService.serverBaseUrl, equals('http://20.70.176.117'));
    });

    test('baseUrl returns local server when isAzureDown is true', () {
      AuthService.isAzureDown = true;
      expect(AuthService.baseUrl, equals('http://192.168.30.98:8000/api/v1'));
      expect(AuthService.serverBaseUrl, equals('http://192.168.30.98:8000'));
    });

    test('switchToLocal and switchToAzure toggle baseUrls accurately', () {
      AuthService.switchToLocal();
      expect(AuthService.isAzureDown, isTrue);
      expect(AuthService.baseUrl, equals('http://192.168.30.98:8000/api/v1'));

      AuthService.switchToAzure();
      expect(AuthService.isAzureDown, isFalse);
      expect(AuthService.baseUrl, equals('http://20.70.176.117/api/v1'));
    });

    test('AuthService returns singleton dio instance with updated baseUrl', () {
      final service1 = AuthService();
      final service2 = AuthService();
      expect(identical(service1, service2), isTrue);

      AuthService.switchToLocal();
      expect(service1.dio.options.baseUrl, equals('http://192.168.30.98:8000/api/v1'));
    });
  });
}
