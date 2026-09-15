import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:local_auth/local_auth.dart';

class SecurityService {
  static const _storage = FlutterSecureStorage();
  static const _biometricKey = 'biometric_lock_enabled';
  static const _pinKey = 'local_app_pin';

  final LocalAuthentication _localAuth = LocalAuthentication();

  Future<bool> isBiometricAvailable() async {
    try {
      final canCheckBiometrics = await _localAuth.canCheckBiometrics;
      if (!canCheckBiometrics) return false;

      final availableBiometrics = await _localAuth.getAvailableBiometrics();
      if (availableBiometrics.isEmpty) return false;

      // canCheckBiometrics only describes hardware capability. The extra
      // device check prevents showing an enabled option when the OS cannot
      // currently provide a secure authentication prompt.
      return await _localAuth.isDeviceSupported();
    } catch (_) {
      return false;
    }
  }

  Future<bool> isBiometricEnabled() async =>
      await _storage.read(key: _biometricKey) == 'true';

  Future<bool> hasPin() async {
    final pin = await _storage.read(key: _pinKey);
    return pin != null && pin.isNotEmpty;
  }

  Future<bool> authenticateBiometric() async {
    try {
      return await _localAuth.authenticate(
        localizedReason:
            'برای ورود به اپلیکیشن راه‌نامه اثر انگشت خود را تأیید کنید',
        biometricOnly: true,
        persistAcrossBackgrounding: true,
      );
    } catch (_) {
      return false;
    }
  }

  Future<bool> setBiometricEnabled(bool enabled) async {
    if (!enabled) {
      await _storage.write(key: _biometricKey, value: 'false');
      return true;
    }
    if (!await isBiometricAvailable() || !await authenticateBiometric()) {
      return false;
    }
    await _storage.write(key: _biometricKey, value: 'true');
    return true;
  }

  Future<bool> verifyPin(String pin) async =>
      await _storage.read(key: _pinKey) == pin;

  /// Used only to bind an existing local PIN to this registered device.
  /// The server receives it over the authenticated API and stores only a hash.
  Future<String?> readPinForDeviceBinding() => _storage.read(key: _pinKey);

  Future<void> savePin(String pin) => _storage.write(key: _pinKey, value: pin);

  Future<void> removePin() => _storage.delete(key: _pinKey);

  Future<void> clearLocalCredentials() async {
    await _storage.delete(key: _pinKey);
    await _storage.delete(key: _biometricKey);
  }
}
