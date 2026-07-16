import 'dart:convert';
import 'dart:io';

import 'package:flutter/services.dart';

import '../config/app_config.dart';
import '../device/device_registration_service.dart';

class AppUpdatePolicy {
  const AppUpdatePolicy({
    required this.configured,
    required this.updateAvailable,
    required this.updateRequired,
    required this.forceUpdate,
    required this.maintenanceMode,
    required this.latestVersion,
    required this.latestBuild,
    required this.downloadUrl,
    required this.message,
    required this.releaseNotes,
  });

  factory AppUpdatePolicy.fromJson(Map<String, dynamic> json) {
    return AppUpdatePolicy(
      configured: json['configured'] == true,
      updateAvailable: json['update_available'] == true,
      updateRequired: json['update_required'] == true,
      forceUpdate: json['force_update'] == true,
      maintenanceMode: json['maintenance_mode'] == true,
      latestVersion: json['latest_version']?.toString(),
      latestBuild: int.tryParse(json['latest_build']?.toString() ?? ''),
      downloadUrl: json['download_url']?.toString(),
      message: json['message']?.toString(),
      releaseNotes: json['release_notes']?.toString(),
    );
  }

  final bool configured;
  final bool updateAvailable;
  final bool updateRequired;
  final bool forceUpdate;
  final bool maintenanceMode;
  final String? latestVersion;
  final int? latestBuild;
  final String? downloadUrl;
  final String? message;
  final String? releaseNotes;

  bool get blocksApp => maintenanceMode || updateRequired;
}

class AppUpdateService {
  static const _channel = MethodChannel(
    'ir.itcakh.dozoleh.mobile_driver/app_update',
  );

  final DeviceRegistrationService _deviceRegistration =
      DeviceRegistrationService();

  Future<AppUpdatePolicy?> fetchPolicy() async {
    final deviceInfo = await _deviceRegistration.getDeviceInfo();
    final build = int.tryParse(deviceInfo?['app_build']?.toString() ?? '') ?? 0;
    final uri = Uri.parse('${AppConfig.publicApi}/mobile-app/version').replace(
      queryParameters: {'platform': 'android', 'build': build.toString()},
    );
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 10);
    try {
      final request = await client.getUrl(uri);
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      final response = await request.close().timeout(
        const Duration(seconds: 15),
      );
      final body = await utf8.decoder.bind(response).join();
      if (response.statusCode < 200 || response.statusCode >= 300) return null;
      final decoded = jsonDecode(body);
      if (decoded is! Map) return null;
      return AppUpdatePolicy.fromJson(Map<String, dynamic>.from(decoded));
    } on Object {
      return null;
    } finally {
      client.close(force: true);
    }
  }

  Future<bool> openDownload(String? url) async {
    if (url == null || url.trim().isEmpty) return false;
    return await _channel.invokeMethod<bool>('openDownload', {
          'url': url.trim(),
        }) ??
        false;
  }
}
