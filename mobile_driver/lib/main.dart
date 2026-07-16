import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'src/auth/auth_repository.dart';
import 'src/auth/login_screen.dart';
import 'src/announcements/startup_announcement_gate.dart';
import 'src/home/home_screen.dart';
import 'src/location/location_gate.dart';
import 'src/notifications/push_notification_service.dart';
import 'src/security/lock_screen.dart';
import 'src/security/security_service.dart';
import 'src/theme/app_theme.dart';
import 'src/update/app_update_gate.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await PushNotificationService.instance.initialize();
  await SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  runApp(const DriverApp());
}

class DriverApp extends StatefulWidget {
  const DriverApp({super.key});

  @override
  State<DriverApp> createState() => _DriverAppState();
}

class _DriverAppState extends State<DriverApp> {
  final AuthRepository _authRepository = AuthRepository();
  final SecurityService _securityService = SecurityService();
  DriverSession? _session;
  bool _restoringSession = true;
  bool _locked = false;
  bool _biometricEnabled = false;
  bool _pinEnabled = false;
  bool _recoveringPin = false;

  @override
  void initState() {
    super.initState();
    _restoreSession();
  }

  Future<void> _restoreSession() async {
    final session = await _authRepository.restoreSession();
    final biometricEnabled = session != null
        ? await _securityService.isBiometricEnabled()
        : false;
    final pinEnabled = session != null
        ? await _securityService.hasPin()
        : false;
    if (!mounted) return;
    setState(() {
      _session = session;
      _biometricEnabled = biometricEnabled;
      _pinEnabled = pinEnabled;
      _locked = session != null && (biometricEnabled || pinEnabled);
      _restoringSession = false;
    });
  }

  void _onLoggedIn(DriverSession session) {
    setState(() {
      _session = session;
      _locked = false;
      _recoveringPin = false;
    });
  }

  Future<void> _lockApp() async {
    final biometricEnabled = await _securityService.isBiometricEnabled();
    final pinEnabled = await _securityService.hasPin();
    if (!mounted) return;
    setState(() {
      _biometricEnabled = biometricEnabled;
      _pinEnabled = pinEnabled;
      _locked = biometricEnabled || pinEnabled;
    });
  }

  void _recoverPin() {
    setState(() {
      _session = null;
      _locked = false;
      _recoveringPin = true;
    });
  }

  Future<void> _logout() async {
    await _authRepository.logout();
    if (!mounted) return;
    setState(() {
      _session = null;
      _recoveringPin = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'دوزوله راننده',
      theme: AppTheme.light,
      locale: const Locale('fa'),
      home: _restoringSession
          ? const _SplashScreen()
          : _session == null
          ? LoginScreen(
              authRepository: _authRepository,
              onLoggedIn: _onLoggedIn,
              initialPasswordRecovery: _recoveringPin,
            )
          : _locked
          ? LockScreen(
              securityService: _securityService,
              allowBiometric: _biometricEnabled,
              allowPin: _pinEnabled,
              onUnlocked: () => setState(() => _locked = false),
              onForgotPin: _recoverPin,
            )
          : AppUpdateGate(
              child: StartupAnnouncementGate(
                apiToken: _session!.token,
                child: LocationGate(
                  child: HomeScreen(
                    session: _session!,
                    onLock: _lockApp,
                    onLogout: _logout,
                  ),
                ),
              ),
            ),
    );
  }
}

class _SplashScreen extends StatelessWidget {
  const _SplashScreen();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: Color(0xFF07111F),
      body: Center(child: CircularProgressIndicator(color: Color(0xFF32D6A0))),
    );
  }
}
