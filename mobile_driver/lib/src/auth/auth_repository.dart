import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../config/app_config.dart';
import '../device/device_registration_service.dart';
import '../notifications/push_notification_service.dart';
import '../security/security_service.dart';

class DriverSession {
  const DriverSession({required this.token, required this.driver});

  final String token;
  final Map<String, dynamic> driver;

  String get name => (driver['name'] ?? 'راننده سامانه').toString();
  String get companyName =>
      (driver['company_name'] ?? 'شرکت حمل‌ونقل').toString();

  Map<String, dynamic> toJson() => {'token': token, 'driver': driver};

  factory DriverSession.fromJson(Map<String, dynamic> json) => DriverSession(
    token: json['token'].toString(),
    driver: Map<String, dynamic>.from(json['driver'] as Map),
  );
}

class AuthException implements Exception {
  const AuthException(this.message);
  final String message;
  @override
  String toString() => message;
}

class AuthRepository {
  static const _storage = FlutterSecureStorage();
  static const _sessionKey = 'driver_session';
  final DeviceRegistrationService _deviceRegistration =
      DeviceRegistrationService();

  Future<bool> isRecognizedDevice(String mobile) async {
    final device = await _deviceRegistration.getDeviceInfo();
    final deviceUuid = device?['device_uuid']?.toString();
    if (deviceUuid == null || deviceUuid.isEmpty) return false;
    final response = await _post('/auth/device-status', {
      'mobile': mobile,
      'device_uuid': deviceUuid,
      'app_identifier': device?['app_identifier']?.toString(),
    });
    return response['recognized'] == true &&
        response['login_method'] == 'device_pin';
  }

  Future<void> requestOtp(String mobile) async {
    await _post('/auth/request-otp', {'mobile': mobile});
  }

  Future<DriverSession> verifyOtp(String mobile, String code) async {
    final response = await _post('/auth/verify-otp', {
      'mobile': mobile,
      'code': code,
    });
    return _storeSession(response, mobile);
  }

  Future<DriverSession> loginWithDevicePin(String mobile, String pin) async {
    final device = await _deviceRegistration.getDeviceInfo();
    final deviceUuid = device?['device_uuid']?.toString();
    if (deviceUuid == null || deviceUuid.isEmpty) {
      throw const AuthException(
        'شناسه این گوشی قابل دریافت نیست. برنامه را بسته و دوباره باز کنید.',
      );
    }
    final response = await _post('/auth/device-login', {
      'mobile': mobile,
      'device_uuid': deviceUuid,
      'pin': pin,
    });
    return _storeSession(response, mobile);
  }

  Future<void> saveDevicePin(DriverSession session, String pin) async {
    try {
      await _deviceRegistration.saveDevicePin(session.token, pin);
    } on DeviceRegistrationException catch (error) {
      throw AuthException(error.message);
    } on SocketException {
      throw const AuthException(
        'اتصال اینترنت برای ثبت رمز این گوشی برقرار نیست.',
      );
    } catch (_) {
      throw const AuthException(
        'ثبت امن رمز این گوشی انجام نشد؛ دوباره تلاش کنید.',
      );
    }
  }

  Future<DriverSession?> restoreSession() async {
    try {
      final raw = await _storage.read(key: _sessionKey);
      if (raw == null || raw.isEmpty) return null;
      final session = DriverSession.fromJson(
        jsonDecode(raw) as Map<String, dynamic>,
      );
      unawaited(_registerDevice(session.token));
      final existingPin = await SecurityService().readPinForDeviceBinding();
      if (existingPin != null && RegExp(r'^\d{4,6}$').hasMatch(existingPin)) {
        unawaited(saveDevicePin(session, existingPin));
      }
      return session;
    } catch (_) {
      await _storage.delete(key: _sessionKey);
      return null;
    }
  }

  Future<void> logout() async {
    await PushNotificationService.instance.deactivateSession();
    await _storage.delete(key: _sessionKey);
  }

  Future<void> _registerDevice(String token) async {
    try {
      await PushNotificationService.instance.activateSession(token);
    } catch (_) {
      // ثبت دستگاه نباید مانع ورود شود؛ در اجرای بعدی دوباره تلاش می‌شود.
    }
  }

  Future<DriverSession> _storeSession(
    Map<String, dynamic> response,
    String mobile,
  ) async {
    final token = response['token']?.toString();
    final driver = response['driver'];
    if (token == null || token.isEmpty || driver is! Map) {
      throw const AuthException('پاسخ ورود از سرور کامل نیست.');
    }
    final driverData = Map<String, dynamic>.from(driver);
    driverData.putIfAbsent('mobile', () => mobile);
    final session = DriverSession(token: token, driver: driverData);
    await _storage.write(key: _sessionKey, value: jsonEncode(session.toJson()));
    unawaited(_registerDevice(session.token));
    return session;
  }

  Future<Map<String, dynamic>> _post(
    String path,
    Map<String, dynamic> payload,
  ) async {
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 15);
    try {
      final request = await client.postUrl(
        Uri.parse('${AppConfig.driverApi}$path'),
      );
      request.headers.contentType = ContentType.json;
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.write(jsonEncode(payload));
      final response = await request.close().timeout(
        const Duration(seconds: 25),
      );
      final body = await utf8.decoder.bind(response).join();
      final decoded = body.isEmpty ? <String, dynamic>{} : jsonDecode(body);
      if (decoded is! Map) {
        throw const AuthException('پاسخ نامعتبر از سرور دریافت شد.');
      }
      final json = Map<String, dynamic>.from(decoded);
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw AuthException(_messageFrom(json, response.statusCode));
      }
      return json;
    } on AuthException {
      rethrow;
    } on SocketException {
      throw const AuthException(
        'اتصال اینترنت برقرار نیست یا سرور در دسترس نیست.',
      );
    } on HandshakeException {
      throw const AuthException('اتصال امن به سامانه برقرار نشد.');
    } on FormatException {
      throw const AuthException('پاسخ نامعتبر از سرور دریافت شد.');
    } catch (_) {
      throw const AuthException(
        'ارتباط با سامانه انجام نشد؛ دوباره تلاش کنید.',
      );
    } finally {
      client.close(force: true);
    }
  }

  String _messageFrom(Map<String, dynamic> json, int statusCode) {
    final message = json['message']?.toString().trim();
    if (message != null && message.isNotEmpty) return message;
    if (statusCode == 404) {
      return 'راننده‌ای با این شماره در سامانه پیدا نشد.';
    }
    if (statusCode == 422) return 'اطلاعات واردشده معتبر نیست.';
    if (statusCode == 429) {
      return 'تعداد تلاش‌ها زیاد است؛ یک دقیقه بعد دوباره امتحان کنید.';
    }
    if (statusCode >= 500) {
      return 'خطایی در سرور رخ داد؛ کمی بعد دوباره تلاش کنید.';
    }
    return 'درخواست ورود انجام نشد.';
  }
}
