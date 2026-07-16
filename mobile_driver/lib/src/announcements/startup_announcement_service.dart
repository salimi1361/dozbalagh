import 'dart:convert';
import 'dart:io';

import '../config/app_config.dart';
import '../device/device_registration_service.dart';

class StartupAnnouncement {
  const StartupAnnouncement({
    required this.id,
    required this.title,
    required this.message,
    required this.priority,
    required this.displayMode,
    required this.requiresAcknowledgement,
    required this.acknowledgementText,
  });

  factory StartupAnnouncement.fromJson(Map<String, dynamic> json) {
    return StartupAnnouncement(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      title: json['title']?.toString() ?? 'اطلاعیه سامانه',
      message: json['message']?.toString() ?? '',
      priority: json['priority']?.toString() ?? 'normal',
      displayMode: json['display_mode']?.toString() ?? 'normal',
      requiresAcknowledgement: json['requires_acknowledgement'] == true,
      acknowledgementText:
          json['acknowledgement_text']?.toString() ?? 'مطالعه کردم',
    );
  }

  final int id;
  final String title;
  final String message;
  final String priority;
  final String displayMode;
  final bool requiresAcknowledgement;
  final String acknowledgementText;
}

class StartupAnnouncementService {
  final DeviceRegistrationService _deviceRegistration =
      DeviceRegistrationService();

  Future<List<StartupAnnouncement>> fetch(String apiToken) async {
    final response = await _request(
      apiToken,
      'GET',
      '${AppConfig.driverApi}/startup-announcements',
    );
    final data = response?['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map(
          (item) =>
              StartupAnnouncement.fromJson(Map<String, dynamic>.from(item)),
        )
        .where((item) => item.id > 0)
        .toList();
  }

  Future<bool> markSeen(String apiToken, int id) async {
    return await _request(
          apiToken,
          'POST',
          '${AppConfig.driverApi}/startup-announcements/$id/seen',
        ) !=
        null;
  }

  Future<bool> acknowledge(String apiToken, int id) async {
    final device = await _deviceRegistration.getDeviceInfo();
    return await _request(
          apiToken,
          'POST',
          '${AppConfig.driverApi}/startup-announcements/$id/acknowledge',
          payload: {'device_uuid': device?['device_uuid']?.toString()},
        ) !=
        null;
  }

  Future<Map<String, dynamic>?> _request(
    String apiToken,
    String method,
    String url, {
    Map<String, dynamic>? payload,
  }) async {
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 10);
    try {
      final uri = Uri.parse(url);
      final request = method == 'GET'
          ? await client.getUrl(uri)
          : await client.postUrl(uri);
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $apiToken');
      if (payload != null) {
        request.headers.contentType = ContentType.json;
        request.write(jsonEncode(payload));
      }
      final response = await request.close().timeout(
        const Duration(seconds: 15),
      );
      final body = await utf8.decoder.bind(response).join();
      if (response.statusCode < 200 || response.statusCode >= 300) return null;
      final decoded = body.isEmpty ? <String, dynamic>{} : jsonDecode(body);
      return decoded is Map ? Map<String, dynamic>.from(decoded) : null;
    } on Object {
      return null;
    } finally {
      client.close(force: true);
    }
  }
}
