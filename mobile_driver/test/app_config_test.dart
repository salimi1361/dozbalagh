import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_driver/src/config/app_config.dart';

void main() {
  test('driver API uses the secure production endpoint', () {
    expect(AppConfig.driverApi, startsWith('https://'));
    expect(AppConfig.driverApi, endsWith('/api/v1/driver'));
  });
}
