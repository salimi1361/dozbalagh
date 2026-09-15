import 'dart:async';

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
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Color(0xFF06101D),
      statusBarIconBrightness: Brightness.light,
      systemStatusBarContrastEnforced: false,
    ),
  );
  runApp(const DriverApp());
  // Show the authentication UI immediately; push setup must not hold the
  // Android launch window on screen.
  unawaited(PushNotificationService.instance.initialize());
  unawaited(
    SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]),
  );
}

class DriverApp extends StatefulWidget {
  const DriverApp({super.key});

  @override
  State<DriverApp> createState() => _DriverAppState();
}

class _DriverAppState extends State<DriverApp> with WidgetsBindingObserver {
  final AuthRepository _authRepository = AuthRepository();
  final SecurityService _securityService = SecurityService();
  DriverSession? _session;
  bool _locked = false;
  bool _biometricEnabled = false;
  bool _pinEnabled = false;
  bool _recoveringPin = false;
  bool _restoringSession = true;
  Timer? _profileTimer;
  StreamSubscription<void>? _profileRefreshSubscription;
  bool _refreshingProfile = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _restoreSession();
    _profileTimer = Timer.periodic(
      const Duration(minutes: 15),
      (_) => _refreshProfile(),
    );
    _profileRefreshSubscription = PushNotificationService
        .instance
        .profileRefreshRequests
        .listen((_) => _refreshProfile());
  }

  @override
  void dispose() {
    _profileTimer?.cancel();
    _profileRefreshSubscription?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _refreshProfile();
    }
  }

  Future<void> _restoreSession() async {
    DriverSession? session;
    var forceOtpLogin = false;
    var biometricEnabled = false;
    var pinEnabled = false;

    try {
      session = await _authRepository.restoreSession();
      forceOtpLogin = _authRepository.takeForceOtpLogin();
      if (session != null) {
        final biometricAvailable = await _securityService
            .isBiometricAvailable();
        biometricEnabled =
            biometricAvailable && await _securityService.isBiometricEnabled();
        pinEnabled = await _securityService.hasPin();
      }
    } catch (_) {
      // A failed restore must fall back to login without leaving a stale
      // login screen visible while the session decision is still pending.
      session = null;
    }

    if (!mounted) return;
    setState(() {
      _session = session;
      _biometricEnabled = biometricEnabled;
      _pinEnabled = pinEnabled;
      _recoveringPin = forceOtpLogin;
      _locked = session != null && (biometricEnabled || pinEnabled);
      _restoringSession = false;
    });
    unawaited(_refreshProfile());
  }

  Future<void> _refreshProfile() async {
    final session = _session;
    if (session == null || _refreshingProfile) return;
    _refreshingProfile = true;
    try {
      final refreshed = await _authRepository.refreshSession(session);
      if (mounted && _session?.token == refreshed.token) {
        setState(() => _session = refreshed);
      }
    } on AuthException catch (error) {
      if (!error.invalidatesSession) return;
      await _authRepository.clearSession(clearSecurity: true);
      if (!mounted || _session?.token != session.token) return;
      setState(() {
        _session = null;
        _locked = false;
        _biometricEnabled = false;
        _pinEnabled = false;
        _recoveringPin = true;
      });
    } catch (_) {
      // Offline use keeps the last securely cached profile.
    } finally {
      _refreshingProfile = false;
    }
  }

  void _onLoggedIn(DriverSession session) {
    setState(() {
      _session = session;
      _locked = false;
      _recoveringPin = false;
    });
  }

  Future<void> _lockApp() async {
    final biometricAvailable = await _securityService.isBiometricAvailable();
    final biometricEnabled =
        biometricAvailable && await _securityService.isBiometricEnabled();
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
      title: 'راه‌نامه',
      theme: AppTheme.light,
      locale: const Locale('fa'),
      home: _restoringSession
          ? const _StartupScreen()
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

class _StartupScreen extends StatelessWidget {
  const _StartupScreen();

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppTheme.navy,
        body: SafeArea(
          child: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Image.asset(
                  'assets/images/association-logo.png',
                  width: 116,
                  height: 116,
                  fit: BoxFit.contain,
                ),
                const SizedBox(height: 18),
                const Text(
                  'راه‌نامه',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 25,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'در حال آماده‌سازی امن برنامه',
                  style: TextStyle(
                    color: Color(0xFFB8C7D9),
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 22),
                const SizedBox(
                  width: 22,
                  height: 22,
                  child: CircularProgressIndicator(
                    strokeWidth: 2.4,
                    color: AppTheme.green,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
