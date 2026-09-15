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

  String get name {
    final firstName = _firstText(driver, const [
      'first_name_fa',
      'first_name',
      'firstNameFa',
      'firstName',
    ]);
    final lastName = _firstText(driver, const [
      'last_name_fa',
      'last_name',
      'lastNameFa',
      'lastName',
    ]);
    final fullName = [
      firstName,
      lastName,
    ].whereType<String>().where((value) => value.isNotEmpty).join(' ').trim();
    if (fullName.isNotEmpty) return fullName;

    return _firstText(driver, const [
          'name',
          'full_name',
          'full_name_fa',
          'driver_name',
          'driverName',
        ]) ??
        'راننده سامانه';
  }

  String get companyName {
    final direct = _firstText(driver, const [
      'company_name',
      'company_name_fa',
      'companyName',
      'company_title',
    ]);
    if (direct != null) return direct;

    final company = driver['company'];
    if (company is Map) {
      return _firstText(Map<String, dynamic>.from(company), const [
            'name_fa',
            'name',
            'title',
          ]) ??
          'شرکت حمل‌ونقل';
    }
    return _text(company) ?? 'شرکت حمل‌ونقل';
  }

  Map<String, dynamic> toJson() => {'token': token, 'driver': driver};

  factory DriverSession.fromJson(Map<String, dynamic> json) => DriverSession(
    token: json['token'].toString(),
    driver: Map<String, dynamic>.from(json['driver'] as Map),
  );
}

String? _text(dynamic value) {
  if (value == null || value is Map || value is List) return null;
  final text = value.toString().trim();
  if (text.isEmpty || text == 'null') return null;
  return text;
}

String? _firstText(Map<String, dynamic> source, List<String> keys) {
  for (final key in keys) {
    final value = _text(source[key]);
    if (value != null) return value;
  }
  return null;
}

class AuthException implements Exception {
  const AuthException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  bool get invalidatesSession =>
      statusCode == 401 ||
      statusCode == 403 ||
      statusCode == 404 ||
      statusCode == 409;

  @override
  String toString() => message;
}

class AuthRepository {
  static const _storage = FlutterSecureStorage();
  static const _sessionKey = 'driver_session_v2';
  static const _legacySessionKey = 'driver_session';
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
    final device = await _deviceRegistration.getDeviceInfo();
    final deviceUuid = device?['device_uuid']?.toString();
    if (deviceUuid == null || deviceUuid.isEmpty) {
      throw const AuthException(
        'شناسه این گوشی قابل دریافت نیست. برنامه را ببندید و دوباره باز کنید.',
      );
    }

    final response = await _post('/auth/verify-otp', {
      'mobile': mobile,
      'code': code,
      'device_uuid': deviceUuid,
      'platform': device?['platform']?.toString() ?? 'android',
      'manufacturer': device?['manufacturer'],
      'model': device?['model'],
      'device_name': device?['device_name'],
      'os_version': device?['os_version'],
      'sdk_version': device?['sdk_version'],
      'app_version': device?['app_version'],
      'app_build': device?['app_build'],
      'app_identifier': device?['app_identifier'],
      'locale': device?['locale'],
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
      if (raw == null || raw.isEmpty) {
        final legacyRaw = await _storage.read(key: _legacySessionKey);
        if (legacyRaw != null && legacyRaw.isNotEmpty) {
          await _storage.delete(key: _legacySessionKey);
          await SecurityService().clearLocalCredentials();
        }
        return null;
      }
      final session = DriverSession.fromJson(
        jsonDecode(raw) as Map<String, dynamic>,
      );

      try {
        // Refresh before the home screen is shown so a changed driver record
        // cannot leave the previous cached identity visible.
        final refreshed = await refreshSession(session);
        if (!await _registerDevice(
          refreshed.token,
          invalidateOnConflict: true,
        )) {
          return null;
        }
        return refreshed;
      } on AuthException catch (error) {
        if (error.invalidatesSession) {
          _forceOtpLogin = true;
          await clearSession(clearSecurity: true);
          return null;
        }
        unawaited(_registerDevice(session.token));
        return session;
      } on SocketException {
        unawaited(_registerDevice(session.token));
        return session;
      } on HandshakeException {
        unawaited(_registerDevice(session.token));
        return session;
      } on TimeoutException {
        unawaited(_registerDevice(session.token));
        return session;
      }
    } catch (_) {
      await _storage.delete(key: _sessionKey);
      return null;
    }
  }

  bool _forceOtpLogin = false;

  bool takeForceOtpLogin() {
    final value = _forceOtpLogin;
    _forceOtpLogin = false;
    return value;
  }

  Future<DriverSession> refreshSession(DriverSession session) async {
    final response = await _get('/auth/profile', session.token);
    final driver = response['driver'];
    if (driver is! Map) {
      throw const AuthException('اطلاعات پروفایل راننده کامل نیست.');
    }
    final currentDriver = Map<String, dynamic>.from(driver);
    if (_identityValue(currentDriver['id']) == null ||
        _identityValue(currentDriver['mobile']) == null) {
      throw const AuthException(
        'اطلاعات هویتی راننده از سرور کامل دریافت نشد؛ لطفاً دوباره وارد شوید.',
        statusCode: 409,
      );
    }
    if (_identityChanged(session.driver, currentDriver)) {
      throw const AuthException(
        'اطلاعات حساب راننده تغییر کرده است؛ لطفاً با شماره موبایل جدید وارد شوید.',
        statusCode: 409,
      );
    }
    // A successful profile response is authoritative. Keeping old display
    // fields here can bring back a driver's previous name or company after
    // an operator edits the record.
    final driverData = currentDriver;
    final refreshed = DriverSession(token: session.token, driver: driverData);
    await _storage.write(
      key: _sessionKey,
      value: jsonEncode(refreshed.toJson()),
    );
    return refreshed;
  }

  Future<void> clearSession({bool clearSecurity = false}) async {
    await PushNotificationService.instance.deactivateSession();
    await _storage.delete(key: _sessionKey);
    await _storage.delete(key: _legacySessionKey);
    if (clearSecurity) {
      await SecurityService().clearLocalCredentials();
    }
  }

  Future<void> logout() async {
    await clearSession();
  }

  Future<bool> _registerDevice(
    String token, {
    bool invalidateOnConflict = false,
  }) async {
    try {
      await PushNotificationService.instance.activateSession(token);
      return true;
    } on DeviceRegistrationException catch (error) {
      if (invalidateOnConflict &&
          (error.statusCode == 409 ||
              error.errorCode == 'DEVICE_ALREADY_BOUND')) {
        _forceOtpLogin = true;
        await clearSession(clearSecurity: true);
        return false;
      }
      return true;
    } catch (_) {
      // ثبت دستگاه نباید مانع ورود شود؛ در اجرای بعدی دوباره تلاش می‌شود.
      return true;
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
        throw AuthException(
          _messageFrom(json, response.statusCode),
          statusCode: response.statusCode,
        );
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

  Future<Map<String, dynamic>> _get(String path, String token) async {
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 15);
    try {
      final request = await client.getUrl(
        Uri.parse('${AppConfig.driverApi}$path'),
      );
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
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
        throw AuthException(
          _messageFrom(json, response.statusCode),
          statusCode: response.statusCode,
        );
      }
      return json;
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

  bool _identityChanged(
    Map<String, dynamic> previous,
    Map<String, dynamic> current,
  ) {
    for (final key in const ['id', 'mobile', 'national_code']) {
      final oldValue = _identityValue(previous[key]);
      final newValue = _identityValue(current[key]);
      if (oldValue != null && newValue != null && oldValue != newValue) {
        return true;
      }
    }
    return false;
  }

  String? _identityValue(dynamic value) {
    final normalized = value?.toString().trim();
    return normalized == null || normalized.isEmpty ? null : normalized;
  }
}
