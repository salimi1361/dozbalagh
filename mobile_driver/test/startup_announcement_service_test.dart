import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_driver/src/announcements/startup_announcement_service.dart';

void main() {
  test('parses blocking driver announcement', () {
    final announcement = StartupAnnouncement.fromJson({
      'id': 12,
      'title': 'پیام فوری',
      'message': 'متن کامل اطلاعیه',
      'priority': 'urgent',
      'display_mode': 'mandatory',
      'requires_acknowledgement': true,
      'acknowledgement_text': 'مطالعه کردم',
    });

    expect(announcement.id, 12);
    expect(announcement.requiresAcknowledgement, isTrue);
    expect(announcement.message, contains('اطلاعیه'));
  });
}
