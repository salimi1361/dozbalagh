import 'package:flutter/services.dart';

class CmrEvidenceLocation {
  const CmrEvidenceLocation({
    required this.latitude,
    required this.longitude,
    required this.accuracy,
  });

  final double latitude;
  final double longitude;
  final double accuracy;
}

class CmrEvidenceService {
  const CmrEvidenceService();

  static const _channel = MethodChannel(
    'ir.itcakh.dozoleh.mobile_driver/cmr_evidence',
  );

  Future<String?> capturePhoto() =>
      _channel.invokeMethod<String>('capturePhoto');

  Future<CmrEvidenceLocation> getCurrentLocation() async {
    final result = await _channel.invokeMapMethod<String, dynamic>(
      'getCurrentLocation',
    );
    final latitude = _number(result?['latitude']);
    final longitude = _number(result?['longitude']);
    if (latitude == null || longitude == null) {
      throw PlatformException(
        code: 'location_unavailable',
        message: 'موقعیت دقیق دریافت نشد.',
      );
    }
    return CmrEvidenceLocation(
      latitude: latitude,
      longitude: longitude,
      accuracy: _number(result?['accuracy']) ?? 0,
    );
  }

  static double? _number(dynamic value) => value is num
      ? value.toDouble()
      : double.tryParse(value?.toString() ?? '');
}
