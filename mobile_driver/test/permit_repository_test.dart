import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_driver/src/trips/permit_repository.dart';

void main() {
  test('renewal permit is identified from request_type', () {
    final permit = DriverPermit.fromJson({
      'id': 42,
      'serial_number': '123',
      'd_code': 'D-42',
      'status_key': 'issued',
      'status_label': 'صادر شده',
      'request_type': 'renewal',
      'dozoleh_items': const [],
    });

    expect(permit.isRenewal, isTrue);
    expect(permit.requestType, 'renewal');
  });

  test('missing request_type remains a new permit', () {
    final permit = DriverPermit.fromJson({
      'id': 43,
      'serial_number': '124',
      'd_code': 'D-43',
      'status_key': 'issued',
      'status_label': 'صادر شده',
      'dozoleh_items': const [],
    });

    expect(permit.isRenewal, isFalse);
    expect(permit.requestType, 'new');
  });
}
