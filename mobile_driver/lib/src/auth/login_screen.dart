import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:smart_auth/smart_auth.dart';

import '../security/security_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_version_label.dart';
import 'auth_repository.dart';

enum _LoginStep { mobile, devicePin, otp, security }

class LoginScreen extends StatefulWidget {
  const LoginScreen({
    required this.authRepository,
    required this.onLoggedIn,
    this.initialPasswordRecovery = false,
    super.key,
  });

  final AuthRepository authRepository;
  final ValueChanged<DriverSession> onLoggedIn;
  final bool initialPasswordRecovery;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _mobileController = TextEditingController();
  final _codeController = TextEditingController();
  final _pinController = TextEditingController();
  final _repeatPinController = TextEditingController();
  final _mobileFocus = FocusNode();
  final _codeFocus = FocusNode();
  final _pinFocus = FocusNode();
  final _smartAuth = SmartAuth.instance;
  final _security = SecurityService();

  _LoginStep _step = _LoginStep.mobile;
  DriverSession? _pendingSession;
  bool _loading = false;
  bool _loginCompleted = false;
  bool _biometricAvailable = false;
  bool _enableBiometric = false;
  late bool _recoveringPin;
  String? _error;
  int _remainingSeconds = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _recoveringPin = widget.initialPasswordRecovery;
  }

  @override
  void dispose() {
    _timer?.cancel();
    _smartAuth.removeUserConsentApiListener();
    _mobileController.dispose();
    _codeController.dispose();
    _pinController.dispose();
    _repeatPinController.dispose();
    _mobileFocus.dispose();
    _codeFocus.dispose();
    _pinFocus.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_loading) return;
    switch (_step) {
      case _LoginStep.mobile:
        await _requestOtp();
      case _LoginStep.devicePin:
        await _loginWithDevicePin();
      case _LoginStep.otp:
        await _verifyOtp();
      case _LoginStep.security:
        await _saveSecurity();
    }
  }

  Future<void> _requestOtp() async {
    final mobile = _mobileController.text.trim();
    if (!RegExp(r'^09\d{9}$').hasMatch(mobile)) {
      setState(() => _error = 'شماره موبایل را به‌صورت ۱۱ رقمی وارد کنید.');
      _mobileFocus.requestFocus();
      return;
    }
    _setLoading();
    try {
      if (!_recoveringPin &&
          await widget.authRepository.isRecognizedDevice(mobile)) {
        if (!mounted) return;
        setState(() => _step = _LoginStep.devicePin);
        _pinFocus.requestFocus();
        return;
      }
      unawaited(_listenForOtp());
      await widget.authRepository.requestOtp(mobile);
      if (!mounted) return;
      setState(() => _step = _LoginStep.otp);
      _startCountdown();
      _codeFocus.requestFocus();
    } on AuthException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } finally {
      _stopLoading();
    }
  }

  Future<void> _loginWithDevicePin() async {
    final pin = _pinController.text;
    if (!RegExp(r'^\d{4,6}$').hasMatch(pin)) {
      setState(() => _error = 'رمز ورود ۴ تا ۶ رقمی را وارد کنید.');
      _pinFocus.requestFocus();
      return;
    }
    _setLoading();
    try {
      final session = await widget.authRepository.loginWithDevicePin(
        _mobileController.text.trim(),
        pin,
      );
      await _security.savePin(pin);
      if (!mounted) return;
      _completeLogin(session);
    } on AuthException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } finally {
      _stopLoading();
    }
  }

  Future<void> _verifyOtp() async {
    final code = _codeController.text.trim();
    if (!RegExp(r'^\d{5}$').hasMatch(code)) {
      setState(() => _error = 'کد تأیید پنج‌رقمی را کامل وارد کنید.');
      _codeFocus.requestFocus();
      return;
    }
    _setLoading();
    try {
      final session = await widget.authRepository.verifyOtp(
        _mobileController.text.trim(),
        code,
      );
      if (!mounted) return;
      if (!_recoveringPin && await _security.hasPin()) {
        _completeLogin(session);
        return;
      }
      final biometricAvailable = await _security.isBiometricAvailable();
      if (!mounted) return;
      _timer?.cancel();
      TextInput.finishAutofillContext();
      setState(() {
        _pendingSession = session;
        _step = _LoginStep.security;
        _biometricAvailable = biometricAvailable;
        _enableBiometric = biometricAvailable;
      });
      _pinFocus.requestFocus();
    } on AuthException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } finally {
      _stopLoading();
    }
  }

  Future<void> _saveSecurity() async {
    final pin = _pinController.text;
    if (!RegExp(r'^\d{4,6}$').hasMatch(pin)) {
      setState(() => _error = 'رمز باید بین ۴ تا ۶ رقم باشد.');
      _pinFocus.requestFocus();
      return;
    }
    if (pin != _repeatPinController.text) {
      setState(() => _error = 'تکرار رمز با رمز انتخابی یکسان نیست.');
      return;
    }
    _setLoading();
    try {
      final session = _pendingSession;
      if (session == null) {
        throw const AuthException('نشست ورود کامل نیست؛ دوباره وارد شوید.');
      }
      await widget.authRepository.saveDevicePin(session, pin);
      await _security.savePin(pin);
      if (_enableBiometric) {
        await _security.setBiometricEnabled(true);
      }
      _completeLogin(session);
    } on AuthException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } finally {
      _stopLoading();
    }
  }

  void _completeLogin(DriverSession session) {
    _loginCompleted = true;
    _timer?.cancel();
    TextInput.finishAutofillContext();
    widget.onLoggedIn(session);
  }

  void _setLoading() {
    setState(() {
      _loading = true;
      _error = null;
    });
  }

  void _stopLoading() {
    if (mounted && !_loginCompleted) setState(() => _loading = false);
  }

  Future<void> _listenForOtp() async {
    final result = await _smartAuth.getSmsWithUserConsentApi();
    if (!mounted || !result.hasData) return;
    final code = result.requireData.code;
    if (code == null || !RegExp(r'^\d{5}$').hasMatch(code)) return;
    _codeController.value = TextEditingValue(
      text: code,
      selection: TextSelection.collapsed(offset: code.length),
    );
    setState(() => _error = null);
  }

  Future<void> _resend() async {
    if (_remainingSeconds > 0 || _loading) return;
    setState(() => _step = _LoginStep.mobile);
    await _requestOtp();
  }

  void _startCountdown() {
    _timer?.cancel();
    setState(() => _remainingSeconds = 180);
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted || _remainingSeconds <= 1) {
        timer.cancel();
        if (mounted) setState(() => _remainingSeconds = 0);
      } else {
        setState(() => _remainingSeconds--);
      }
    });
  }

  String get _countdownText {
    final minutes = (_remainingSeconds ~/ 60).toString().padLeft(2, '0');
    final seconds = (_remainingSeconds % 60).toString().padLeft(2, '0');
    return '$minutes:$seconds';
  }

  void _changeNumber() {
    _timer?.cancel();
    setState(() {
      _step = _LoginStep.mobile;
      _codeController.clear();
      _pinController.clear();
      _error = null;
    });
    _mobileFocus.requestFocus();
  }

  void _startPasswordRecovery() {
    _timer?.cancel();
    setState(() {
      _recoveringPin = true;
      _step = _LoginStep.mobile;
      _codeController.clear();
      _pinController.clear();
      _repeatPinController.clear();
      _error = null;
    });
    _mobileFocus.requestFocus();
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        body: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topRight,
              end: Alignment.bottomLeft,
              colors: [Color(0xFF06101D), Color(0xFF0C3153), Color(0xFF071827)],
            ),
          ),
          child: SafeArea(
            child: Stack(
              children: [
                const Positioned(
                  top: -90,
                  left: -70,
                  child: _Glow(size: 240, color: Color(0x3324D6A4)),
                ),
                const Positioned(
                  top: 160,
                  right: -110,
                  child: _Glow(size: 280, color: Color(0x332168D8)),
                ),
                Center(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 430),
                      child: Column(
                        children: [
                          const _BrandHeader(),
                          const SizedBox(height: 20),
                          _StepIndicator(
                            step: switch (_step) {
                              _LoginStep.mobile => 0,
                              _LoginStep.devicePin || _LoginStep.otp => 1,
                              _LoginStep.security => 2,
                            },
                          ),
                          const SizedBox(height: 14),
                          _LoginCard(
                            step: _step,
                            loading: _loading,
                            error: _error,
                            mobileController: _mobileController,
                            codeController: _codeController,
                            pinController: _pinController,
                            repeatPinController: _repeatPinController,
                            mobileFocus: _mobileFocus,
                            codeFocus: _codeFocus,
                            pinFocus: _pinFocus,
                            countdownText: _countdownText,
                            canResend: _remainingSeconds == 0,
                            biometricAvailable: _biometricAvailable,
                            enableBiometric: _enableBiometric,
                            recoveringPin: _recoveringPin,
                            onBiometricChanged: (value) =>
                                setState(() => _enableBiometric = value),
                            onSubmit: _submit,
                            onResend: _resend,
                            onChangeNumber: _changeNumber,
                            onForgotPin: _startPasswordRecovery,
                          ),
                          const SizedBox(height: 14),
                          const _LoginBenefits(),
                          const SizedBox(height: 12),
                          const AppVersionLabel(
                            color: Color(0xFF91A8BE),
                            fontSize: 9,
                          ),
                        ],
                      ),
                    ),
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

class _Glow extends StatelessWidget {
  const _Glow({required this.size, required this.color});
  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(colors: [color, Colors.transparent]),
      ),
    );
  }
}

class _BrandHeader extends StatelessWidget {
  const _BrandHeader();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          width: 82,
          height: 82,
          padding: const EdgeInsets.all(9),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(25),
            border: Border.all(color: const Color(0x5578F5D0)),
            boxShadow: const [
              BoxShadow(color: Color(0x5521A8FF), blurRadius: 30),
            ],
          ),
          child: Image.asset('assets/images/association-logo.png'),
        ),
        const SizedBox(height: 13),
        const Text(
          'دوزوله راننده',
          style: TextStyle(
            color: Colors.white,
            fontSize: 25,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 4),
        const Text(
          'همراه امن رانندگان ترانزیت بین‌المللی',
          style: TextStyle(
            color: Color(0xFFB8C7D9),
            fontSize: 11,
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    );
  }
}

class _StepIndicator extends StatelessWidget {
  const _StepIndicator({required this.step});
  final int step;

  @override
  Widget build(BuildContext context) {
    const labels = ['شماره', 'تأیید', 'امنیت'];
    return Row(
      children: List.generate(3, (index) {
        final active = index <= step;
        return Expanded(
          child: Row(
            children: [
              Expanded(
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 220),
                  height: 3,
                  decoration: BoxDecoration(
                    color: active
                        ? const Color(0xFF62EBC8)
                        : const Color(0xFF334155),
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ),
              const SizedBox(width: 5),
              Text(
                labels[index],
                style: TextStyle(
                  color: active
                      ? const Color(0xFFD9FFF5)
                      : const Color(0xFF64748B),
                  fontSize: 9,
                  fontWeight: FontWeight.w800,
                ),
              ),
              if (index < 2) const SizedBox(width: 8),
            ],
          ),
        );
      }),
    );
  }
}

class _LoginCard extends StatelessWidget {
  const _LoginCard({
    required this.step,
    required this.loading,
    required this.error,
    required this.mobileController,
    required this.codeController,
    required this.pinController,
    required this.repeatPinController,
    required this.mobileFocus,
    required this.codeFocus,
    required this.pinFocus,
    required this.countdownText,
    required this.canResend,
    required this.biometricAvailable,
    required this.enableBiometric,
    required this.recoveringPin,
    required this.onBiometricChanged,
    required this.onSubmit,
    required this.onResend,
    required this.onChangeNumber,
    required this.onForgotPin,
  });

  final _LoginStep step;
  final bool loading;
  final String? error;
  final TextEditingController mobileController;
  final TextEditingController codeController;
  final TextEditingController pinController;
  final TextEditingController repeatPinController;
  final FocusNode mobileFocus;
  final FocusNode codeFocus;
  final FocusNode pinFocus;
  final String countdownText;
  final bool canResend;
  final bool biometricAvailable;
  final bool enableBiometric;
  final bool recoveringPin;
  final ValueChanged<bool> onBiometricChanged;
  final VoidCallback onSubmit;
  final VoidCallback onResend;
  final VoidCallback onChangeNumber;
  final VoidCallback onForgotPin;

  @override
  Widget build(BuildContext context) {
    return AnimatedSize(
      duration: const Duration(milliseconds: 280),
      curve: Curves.easeOut,
      child: Container(
        padding: const EdgeInsets.fromLTRB(21, 22, 21, 20),
        decoration: BoxDecoration(
          color: const Color(0xFFFDFEFF),
          borderRadius: BorderRadius.circular(27),
          border: Border.all(color: Colors.white),
          boxShadow: const [
            BoxShadow(
              color: Color(0x55000000),
              blurRadius: 34,
              offset: Offset(0, 18),
            ),
          ],
        ),
        child: AnimatedSwitcher(
          duration: const Duration(milliseconds: 220),
          child: Column(
            key: ValueKey(step),
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 21,
                    backgroundColor: const Color(0xFFEAF4FF),
                    foregroundColor: AppTheme.blue,
                    child: Icon(switch (step) {
                      _LoginStep.mobile => Icons.phone_android_rounded,
                      _LoginStep.devicePin => Icons.password_rounded,
                      _LoginStep.otp => Icons.sms_rounded,
                      _LoginStep.security => Icons.shield_rounded,
                    }),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          switch (step) {
                            _LoginStep.mobile =>
                              recoveringPin
                                  ? 'بازیابی رمز ورود'
                                  : 'ورود راننده',
                            _LoginStep.devicePin => 'ورود امن با رمز گوشی',
                            _LoginStep.otp => 'تأیید شماره موبایل',
                            _LoginStep.security => 'امنیت ورودهای بعدی',
                          },
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF111827),
                          ),
                        ),
                        Text(
                          switch (step) {
                            _LoginStep.mobile =>
                              recoveringPin
                                  ? 'شماره ثبت‌شده را وارد کنید تا کد موقت ارسال شود'
                                  : 'شماره ثبت‌شده در سامانه را وارد کنید',
                            _LoginStep.devicePin =>
                              'این گوشی قبلاً برای ${mobileController.text} ثبت شده است',
                            _LoginStep.otp =>
                              'کد ارسال‌شده به ${mobileController.text}',
                            _LoginStep.security =>
                              'یک رمز کوتاه برای همین گوشی بسازید',
                          },
                          style: const TextStyle(
                            color: Color(0xFF64748B),
                            fontSize: 10,
                            height: 1.6,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              if (step == _LoginStep.mobile) ...[
                TextField(
                  controller: mobileController,
                  focusNode: mobileFocus,
                  autofocus: true,
                  keyboardType: TextInputType.phone,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.center,
                  maxLength: 11,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  autofillHints: const [AutofillHints.telephoneNumber],
                  decoration: const InputDecoration(
                    counterText: '',
                    hintText: '09123456789',
                    prefixIcon: Icon(Icons.phone_android_rounded),
                  ),
                  onSubmitted: (_) => onSubmit(),
                ),
                const SizedBox(height: 12),
                const _OneDeviceNotice(),
                const SizedBox(height: 4),
                if (recoveringPin)
                  const _RecoveryNotice()
                else
                  Align(
                    alignment: Alignment.center,
                    child: TextButton.icon(
                      onPressed: loading ? null : onForgotPin,
                      icon: const Icon(Icons.lock_reset_rounded, size: 20),
                      label: const Text('رمز ورود را فراموش کرده‌ام'),
                    ),
                  ),
              ] else if (step == _LoginStep.devicePin) ...[
                TextField(
                  controller: pinController,
                  focusNode: pinFocus,
                  autofocus: true,
                  keyboardType: TextInputType.number,
                  obscureText: true,
                  maxLength: 6,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.center,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: const InputDecoration(
                    counterText: '',
                    labelText: 'رمز ورود ۴ تا ۶ رقمی',
                    prefixIcon: Icon(Icons.password_rounded),
                  ),
                  onSubmitted: (_) => onSubmit(),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(11),
                  decoration: BoxDecoration(
                    color: const Color(0xFFECFDF5),
                    borderRadius: BorderRadius.circular(13),
                  ),
                  child: const Text(
                    'گوشی شما شناسایی شد؛ برای این ورود پیامک ارسال نمی‌شود.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Color(0xFF047857),
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                Align(
                  alignment: Alignment.center,
                  child: TextButton.icon(
                    onPressed: loading ? null : onForgotPin,
                    icon: const Icon(Icons.sms_outlined, size: 19),
                    label: const Text(
                      'رمز را فراموش کرده‌ام؛ ارسال کد یک‌بارمصرف',
                    ),
                  ),
                ),
              ] else if (step == _LoginStep.otp) ...[
                TextField(
                  controller: codeController,
                  focusNode: codeFocus,
                  autofocus: true,
                  keyboardType: TextInputType.number,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.center,
                  maxLength: 5,
                  style: const TextStyle(
                    fontSize: 25,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 10,
                  ),
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  autofillHints: const [AutofillHints.oneTimeCode],
                  decoration: const InputDecoration(
                    counterText: '',
                    hintText: '•••••',
                  ),
                  onSubmitted: (_) => onSubmit(),
                ),
              ] else ...[
                TextField(
                  controller: pinController,
                  focusNode: pinFocus,
                  autofocus: true,
                  keyboardType: TextInputType.number,
                  obscureText: true,
                  maxLength: 6,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.center,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: const InputDecoration(
                    counterText: '',
                    labelText: 'رمز ۴ تا ۶ رقمی',
                    prefixIcon: Icon(Icons.password_rounded),
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: repeatPinController,
                  keyboardType: TextInputType.number,
                  obscureText: true,
                  maxLength: 6,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.center,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: const InputDecoration(
                    counterText: '',
                    labelText: 'تکرار رمز',
                    prefixIcon: Icon(Icons.lock_outline_rounded),
                  ),
                  onSubmitted: (_) => onSubmit(),
                ),
                if (biometricAvailable)
                  SwitchListTile(
                    contentPadding: EdgeInsets.zero,
                    secondary: const Icon(Icons.fingerprint_rounded),
                    title: const Text(
                      'فعال‌سازی اثر انگشت',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                    subtitle: const Text(
                      'دفعات بعد بدون پیامک وارد شوید',
                      style: TextStyle(fontSize: 10),
                    ),
                    value: enableBiometric,
                    onChanged: loading ? null : onBiometricChanged,
                  ),
              ],
              if (error != null) ...[
                const SizedBox(height: 11),
                Container(
                  padding: const EdgeInsets.all(11),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF1F2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    error!,
                    style: const TextStyle(
                      color: Color(0xFFBE123C),
                      fontSize: 10,
                      height: 1.6,
                    ),
                  ),
                ),
              ],
              const SizedBox(height: 16),
              FilledButton(
                onPressed: loading ? null : onSubmit,
                style: FilledButton.styleFrom(
                  minimumSize: const Size.fromHeight(53),
                  backgroundColor: step == _LoginStep.security
                      ? AppTheme.green
                      : AppTheme.blue,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
                child: loading
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.3,
                          color: Colors.white,
                        ),
                      )
                    : Text(switch (step) {
                        _LoginStep.mobile => 'دریافت کد ورود',
                        _LoginStep.devicePin => 'ورود با رمز',
                        _LoginStep.otp => 'تأیید کد و ادامه',
                        _LoginStep.security => 'ذخیره رمز و ورود',
                      }, style: const TextStyle(fontWeight: FontWeight.w900)),
              ),
              if (step == _LoginStep.otp) ...[
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    TextButton(
                      onPressed: loading ? null : onChangeNumber,
                      child: const Text('اصلاح شماره'),
                    ),
                    TextButton(
                      onPressed: canResend && !loading ? onResend : null,
                      child: Text(
                        canResend ? 'ارسال مجدد' : 'ارسال مجدد $countdownText',
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _OneDeviceNotice extends StatelessWidget {
  const _OneDeviceNotice();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF8E7),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFF4D58A)),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.phonelink_lock_rounded, color: Color(0xFFB7791F)),
          SizedBox(width: 9),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'این حساب مخصوص یک دستگاه اصلی است',
                  style: TextStyle(
                    color: Color(0xFF7C4A03),
                    fontSize: 11,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                SizedBox(height: 3),
                Text(
                  'اپلیکیشن را روی گوشی اصلی خود نصب کنید. برای تعویض گوشی با شرکت هماهنگ کنید.',
                  style: TextStyle(
                    color: Color(0xFF8A5A13),
                    fontSize: 9,
                    height: 1.55,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _RecoveryNotice extends StatelessWidget {
  const _RecoveryNotice();

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(top: 8),
      padding: const EdgeInsets.all(11),
      decoration: BoxDecoration(
        color: const Color(0xFFEFF6FF),
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: const Color(0xFFBFDBFE)),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.verified_user_outlined, color: AppTheme.blue, size: 21),
          SizedBox(width: 8),
          Expanded(
            child: Text(
              'پس از تأیید کد پیامکی، رمز قبلی با رمز جدیدی که انتخاب می‌کنید جایگزین می‌شود.',
              style: TextStyle(
                color: Color(0xFF1E3A5F),
                fontSize: 9,
                height: 1.6,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LoginBenefits extends StatelessWidget {
  const _LoginBenefits();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: const Color(0x1814B8A6),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0x3345E0C0)),
      ),
      child: const Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _Benefit(icon: Icons.fingerprint_rounded, label: 'ورود سریع'),
          _Benefit(icon: Icons.lock_rounded, label: 'نشست رمزگذاری‌شده'),
          _Benefit(icon: Icons.sms_outlined, label: 'پیامک فقط بار اول'),
        ],
      ),
    );
  }
}

class _Benefit extends StatelessWidget {
  const _Benefit({required this.icon, required this.label});
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, color: const Color(0xFF78F5D0), size: 20),
        const SizedBox(height: 4),
        Text(
          label,
          style: const TextStyle(
            color: Color(0xFFD9F7EE),
            fontSize: 8,
            fontWeight: FontWeight.w800,
          ),
        ),
      ],
    );
  }
}
