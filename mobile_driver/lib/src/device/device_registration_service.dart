import 'dart:convert';
import 'dart:io';

import 'package:flutter/services.dart';

import '../config/app_config.dart';

class DeviceRegistrationService {
  static const _channel = MethodChannel(
    'ir.itcakh.dozoleh.mobile_driver/device_info',
  );

  Future<Map<String, dynamic>?> getDeviceInfo() async {
    return _channel.invokeMapMethod<String, dynamic>('getDeviceInfo');
  }

  Future<void> register(
    String token, {
    String? fcmToken,
    bool? notificationsEnabled,
  }) async {
    final raw = await getDeviceInfo();
    if (raw == null || raw['device_uuid']?.toString().isEmpty != false) return;

    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 10);
    try {
      final request = await client.postUrl(
        Uri.parse('${AppConfig.driverApi}/app-installations'),
      );
      request.headers.contentType = ContentType.json;
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
      final payload = Map<String, dynamic>.from(raw);
      if (fcmToken != null) payload['fcm_token'] = fcmToken;
      if (notificationsEnabled != null) {
        payload['notifications_enabled'] = notificationsEnabled;
      }
      request.write(jsonEncode(payload));
      final response = await request.close().timeout(
        const Duration(seconds: 15),
      );
      await response.drain<void>();
    } finally {
      client.close(force: true);
    }
  }

  Future<void> saveDevicePin(String token, String pin) async {
    final raw = await getDeviceInfo();
    final deviceUuid = raw?['device_uuid']?.toString();
    if (deviceUuid == null || deviceUuid.isEmpty) {
      throw const DeviceRegistrationException(
        'شناسه این گوشی قابل دریافت نیست. برنامه را بسته و دوباره باز کنید.',
      );
    }

    // The installation must exist before a PIN can be bound to it.
    await register(token);
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 10);
    try {
      final request = await client.postUrl(
        Uri.parse('${AppConfig.driverApi}/auth/device-pin'),
      );
      request.headers.contentType = ContentType.json;
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
      request.write(jsonEncode({'device_uuid': deviceUuid, 'pin': pin}));
      final response = await request.close().timeout(
        const Duration(seconds: 15),
      );
      final body = await utf8.decoder.bind(response).join();
      if (response.statusCode < 200 || response.statusCode >= 300) {
        String? message;
        try {
          message = (jsonDecode(body) as Map)['message']?.toString();
        } catch (_) {}
        throw DeviceRegistrationException(
          message ?? 'ثبت رمز این گوشی در سامانه انجام نشد.',
        );
      }
    } finally {
      client.close(force: true);
    }
  }
}

class DeviceRegistrationException implements Exception {
  const DeviceRegistrationException(this.message);
  final String message;

  @override
  String toString() => message;
}
