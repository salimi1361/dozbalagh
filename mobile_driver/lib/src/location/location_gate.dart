import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../theme/app_theme.dart';

class LocationGate extends StatefulWidget {
  const LocationGate({required this.child, super.key});

  final Widget child;

  @override
  State<LocationGate> createState() => _LocationGateState();
}

enum _GateState { checking, ready, serviceOff, permissionMissing, error }

class _LocationGateState extends State<LocationGate>
    with WidgetsBindingObserver {
  static const _channel = MethodChannel(
    'ir.itcakh.dozoleh.mobile_driver/location_access',
  );

  _GateState _state = _GateState.checking;
  Timer? _timer;
  bool _checking = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _checkLocation();
    _timer = Timer.periodic(
      const Duration(seconds: 2),
      (_) => _checkLocation(silent: true),
    );
  }

  @override
  void dispose() {
    _timer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _checkLocation();
    }
  }

  Future<void> _checkLocation({bool silent = false}) async {
    if (_checking) return;
    _checking = true;
    if (!silent && mounted && _state != _GateState.ready) {
      setState(() => _state = _GateState.checking);
    }
    try {
      final result = await _channel.invokeMapMethod<String, dynamic>(
        'getLocationState',
      );
      final serviceEnabled = result?['service_enabled'] == true;
      final permissionGranted = result?['permission_granted'] == true;
      final nextState = !serviceEnabled
          ? _GateState.serviceOff
          : !permissionGranted
          ? _GateState.permissionMissing
          : _GateState.ready;
      if (mounted && nextState != _state) {
        setState(() => _state = nextState);
      }
    } on PlatformException {
      if (mounted && _state != _GateState.error) {
        setState(() => _state = _GateState.error);
      }
    } finally {
      _checking = false;
    }
  }

  Future<void> _performPrimaryAction() async {
    switch (_state) {
      case _GateState.serviceOff:
        await _channel.invokeMethod<void>('openLocationSettings');
      case _GateState.permissionMissing:
        await _channel.invokeMethod<bool>('requestPermission');
        await _checkLocation();
      case _GateState.error:
        await _checkLocation();
      case _GateState.checking:
      case _GateState.ready:
        return;
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_state == _GateState.ready) return widget.child;

    final checking = _state == _GateState.checking;
    final serviceOff = _state == _GateState.serviceOff;
    final permissionMissing = _state == _GateState.permissionMissing;
    final title = checking
        ? 'در حال بررسی موقعیت مکانی'
        : serviceOff
        ? 'موقعیت مکانی گوشی خاموش است'
        : permissionMissing
        ? 'دسترسی موقعیت مکانی لازم است'
        : 'بررسی GPS انجام نشد';
    final description = serviceOff
        ? 'برای فعالیت اپلیکیشن، GPS گوشی را روشن کنید و سپس به برنامه برگردید.'
        : permissionMissing
        ? 'برای ثبت صحیح موقعیت سفر، دسترسی «موقعیت مکانی دقیق» را به برنامه بدهید.'
        : 'برنامه برای ادامه فعالیت باید وضعیت GPS گوشی را بررسی کند.';
    final buttonText = serviceOff
        ? 'روشن‌کردن GPS'
        : permissionMissing
        ? 'اعطای دسترسی دقیق'
        : 'بررسی دوباره';

    return Scaffold(
      backgroundColor: AppTheme.navy,
      body: SafeArea(
        child: Directionality(
          textDirection: TextDirection.rtl,
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(28),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 430),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      width: 112,
                      height: 112,
                      decoration: BoxDecoration(
                        color: const Color(0xFF10243A),
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: const Color(0xFF32D6A0).withValues(alpha: .6),
                          width: 2,
                        ),
                      ),
                      child: checking
                          ? const Padding(
                              padding: EdgeInsets.all(38),
                              child: CircularProgressIndicator(
                                color: Color(0xFF32D6A0),
                                strokeWidth: 3,
                              ),
                            )
                          : Icon(
                              serviceOff
                                  ? Icons.location_off_rounded
                                  : Icons.my_location_rounded,
                              color: const Color(0xFF32D6A0),
                              size: 54,
                            ),
                    ),
                    const SizedBox(height: 28),
                    Text(
                      title,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      description,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Color(0xFFB7C5D5),
                        fontSize: 14,
                        height: 1.8,
                      ),
                    ),
                    if (!checking) ...[
                      const SizedBox(height: 30),
                      SizedBox(
                        width: double.infinity,
                        height: 54,
                        child: FilledButton.icon(
                          onPressed: _performPrimaryAction,
                          icon: Icon(
                            serviceOff
                                ? Icons.settings_rounded
                                : Icons.gps_fixed_rounded,
                          ),
                          label: Text(buttonText),
                          style: FilledButton.styleFrom(
                            backgroundColor: const Color(0xFF12B886),
                            foregroundColor: Colors.white,
                            textStyle: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ),
                      if (permissionMissing) ...[
                        const SizedBox(height: 10),
                        TextButton(
                          onPressed: () =>
                              _channel.invokeMethod<void>('openAppSettings'),
                          child: const Text(
                            'بازکردن تنظیمات برنامه',
                            style: TextStyle(color: Color(0xFF8FC8FF)),
                          ),
                        ),
                      ],
                      const SizedBox(height: 16),
                      const Text(
                        'تا زمان روشن‌شدن GPS و تأیید دسترسی دقیق، امکان استفاده از برنامه وجود ندارد.',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: Color(0xFF7F91A5),
                          fontSize: 12,
                          height: 1.7,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
