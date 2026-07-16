import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../theme/app_theme.dart';
import 'security_service.dart';

class LockScreen extends StatefulWidget {
  const LockScreen({
    required this.securityService,
    required this.allowBiometric,
    required this.allowPin,
    required this.onUnlocked,
    required this.onForgotPin,
    super.key,
  });

  final SecurityService securityService;
  final bool allowBiometric;
  final bool allowPin;
  final VoidCallback onUnlocked;
  final VoidCallback onForgotPin;

  @override
  State<LockScreen> createState() => _LockScreenState();
}

class _LockScreenState extends State<LockScreen> {
  final _pinController = TextEditingController();
  bool _authenticating = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    if (widget.allowBiometric) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _authenticate());
    }
  }

  @override
  void dispose() {
    _pinController.dispose();
    super.dispose();
  }

  Future<void> _authenticate() async {
    if (_authenticating) return;
    setState(() {
      _authenticating = true;
      _error = null;
    });
    final authenticated = await widget.securityService.authenticateBiometric();
    if (!mounted) return;
    if (authenticated) {
      widget.onUnlocked();
      return;
    }
    setState(() {
      _authenticating = false;
      _error = 'اثر انگشت تأیید نشد. دوباره تلاش کنید.';
    });
  }

  Future<void> _verifyPin() async {
    if (await widget.securityService.verifyPin(_pinController.text)) {
      widget.onUnlocked();
      return;
    }
    if (mounted) setState(() => _error = 'رمز ورود برنامه صحیح نیست.');
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppTheme.navy,
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(28),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 390),
                child: Column(
                  children: [
                    const Icon(
                      Icons.fingerprint_rounded,
                      size: 88,
                      color: Color(0xFF78F5D0),
                    ),
                    const SizedBox(height: 18),
                    const Text(
                      'قفل امنیتی دوزوله',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 23,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'برای ادامه هویت خود را تأیید کنید.',
                      style: TextStyle(color: Color(0xFFB8C7D9)),
                    ),
                    const SizedBox(height: 28),
                    if (widget.allowBiometric)
                      FilledButton.icon(
                        onPressed: _authenticating ? null : _authenticate,
                        icon: const Icon(Icons.fingerprint_rounded),
                        label: Text(
                          _authenticating
                              ? 'در حال بررسی...'
                              : 'ورود با اثر انگشت',
                        ),
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(54),
                          backgroundColor: AppTheme.green,
                        ),
                      ),
                    if (widget.allowPin) ...[
                      if (widget.allowBiometric)
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 18),
                          child: Text(
                            'یا',
                            style: TextStyle(color: Color(0xFF94A3B8)),
                          ),
                        )
                      else
                        const SizedBox(height: 20),
                      TextField(
                        controller: _pinController,
                        keyboardType: TextInputType.number,
                        obscureText: true,
                        maxLength: 6,
                        textDirection: TextDirection.ltr,
                        textAlign: TextAlign.center,
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                        ],
                        decoration: const InputDecoration(
                          filled: true,
                          fillColor: Colors.white,
                          counterText: '',
                          hintText: 'رمز ورود برنامه',
                        ),
                        onSubmitted: (_) => _verifyPin(),
                      ),
                      const SizedBox(height: 10),
                      OutlinedButton(
                        onPressed: _verifyPin,
                        style: OutlinedButton.styleFrom(
                          minimumSize: const Size.fromHeight(50),
                          foregroundColor: Colors.white,
                          side: const BorderSide(color: Color(0xFF64748B)),
                        ),
                        child: const Text('ورود با رمز برنامه'),
                      ),
                      const SizedBox(height: 6),
                      TextButton.icon(
                        onPressed: _authenticating ? null : widget.onForgotPin,
                        icon: const Icon(Icons.help_outline_rounded, size: 19),
                        label: const Text('رمز ورود را فراموش کرده‌ام'),
                        style: TextButton.styleFrom(
                          foregroundColor: const Color(0xFF78F5D0),
                        ),
                      ),
                    ],
                    if (_error != null) ...[
                      const SizedBox(height: 14),
                      Text(
                        _error!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Color(0xFFFDA4AF)),
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
