import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_driver/src/update/app_update_service.dart';

void main() {
  test('parses mandatory update and release notes', () {
    final policy = AppUpdatePolicy.fromJson({
      'configured': true,
      'update_available': true,
      'update_required': true,
      'force_update': true,
      'maintenance_mode': false,
      'latest_version': '2.0.0',
      'latest_build': 20,
      'download_url': 'https://example.test/app.apk',
      'message': 'نسخه جدید را نصب کنید.',
      'release_notes': 'بهبود ردیابی و اعلان‌ها',
    });

    expect(policy.blocksApp, isTrue);
    expect(policy.latestBuild, 20);
    expect(policy.releaseNotes, contains('ردیابی'));
  });
}
