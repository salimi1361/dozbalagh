import 'dart:async';

import 'package:flutter/material.dart';

import '../notifications/push_notification_service.dart';
import 'app_update_service.dart';

class AppUpdateGate extends StatefulWidget {
  const AppUpdateGate({required this.child, super.key});

  final Widget child;

  @override
  State<AppUpdateGate> createState() => _AppUpdateGateState();
}

class _AppUpdateGateState extends State<AppUpdateGate> {
  final AppUpdateService _service = AppUpdateService();
  StreamSubscription<void>? _pushSubscription;
  AppUpdatePolicy? _blockingPolicy;
  bool _checking = true;
  bool _optionalDialogVisible = false;

  @override
  void initState() {
    super.initState();
    _pushSubscription = PushNotificationService.instance.appUpdateRequests
        .listen((_) => _check(showOptional: true));
    _check(
      showOptional: PushNotificationService.instance.takePendingAppUpdate(),
    );
  }

  @override
  void dispose() {
    _pushSubscription?.cancel();
    super.dispose();
  }

  Future<void> _check({required bool showOptional}) async {
    final policy = await _service.fetchPolicy();
    if (!mounted) return;
    setState(() {
      _checking = false;
      _blockingPolicy = policy?.blocksApp == true ? policy : null;
    });
    if (policy?.updateAvailable == true && !policy!.blocksApp) {
      // در شروع برنامه نیز نسخه اختیاری یک بار نمایش داده می‌شود؛ Push آن را فوری تازه می‌کند.
      await _showOptionalPolicy(policy);
    }
  }

  Future<void> _showOptionalPolicy(AppUpdatePolicy policy) async {
    if (!mounted || _optionalDialogVisible) return;
    _optionalDialogVisible = true;
    await showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('نسخه جدید آماده است'),
        content: _PolicyDetails(policy: policy),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('بعداً'),
          ),
          FilledButton(
            onPressed: () => _service.openDownload(policy.downloadUrl),
            child: const Text('دریافت بروزرسانی'),
          ),
        ],
      ),
    );
    _optionalDialogVisible = false;
  }

  @override
  Widget build(BuildContext context) {
    if (_checking) {
      return const Scaffold(
        backgroundColor: Color(0xFF07111F),
        body: Center(
          child: CircularProgressIndicator(color: Color(0xFF32D6A0)),
        ),
      );
    }
    final policy = _blockingPolicy;
    if (policy == null) return widget.child;
    return PopScope(
      canPop: false,
      child: Scaffold(
        backgroundColor: const Color(0xFFF4F7FB),
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 480),
                child: Card(
                  elevation: 0,
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          policy.maintenanceMode
                              ? Icons.build_circle_outlined
                              : Icons.system_update_alt_rounded,
                          size: 64,
                          color: policy.maintenanceMode
                              ? Colors.orange
                              : const Color(0xFF087F5B),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          policy.maintenanceMode
                              ? 'سامانه در حال بروزرسانی است'
                              : 'بروزرسانی الزامی',
                          style: Theme.of(context).textTheme.headlineSmall
                              ?.copyWith(fontWeight: FontWeight.w900),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        _PolicyDetails(policy: policy),
                        const SizedBox(height: 20),
                        if (!policy.maintenanceMode)
                          SizedBox(
                            width: double.infinity,
                            child: FilledButton.icon(
                              onPressed: () =>
                                  _service.openDownload(policy.downloadUrl),
                              icon: const Icon(Icons.download_rounded),
                              label: const Padding(
                                padding: EdgeInsets.symmetric(vertical: 12),
                                child: Text('دریافت و نصب نسخه جدید'),
                              ),
                            ),
                          ),
                        const SizedBox(height: 8),
                        TextButton.icon(
                          onPressed: () => _check(showOptional: false),
                          icon: const Icon(Icons.refresh_rounded),
                          label: const Text('بررسی مجدد'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _PolicyDetails extends StatelessWidget {
  const _PolicyDetails({required this.policy});

  final AppUpdatePolicy policy;

  @override
  Widget build(BuildContext context) {
    final notes = policy.releaseNotes?.trim();
    final message = policy.message?.trim();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (policy.latestVersion?.isNotEmpty == true)
          Text(
            'نسخه ${policy.latestVersion}  •  Build ${policy.latestBuild ?? '-'}',
            textAlign: TextAlign.center,
            style: const TextStyle(fontWeight: FontWeight.w800),
          ),
        if (message?.isNotEmpty == true) ...[
          const SizedBox(height: 12),
          Text(message!, textAlign: TextAlign.center),
        ],
        if (notes?.isNotEmpty == true) ...[
          const SizedBox(height: 16),
          const Text(
            'تغییرات این نسخه',
            style: TextStyle(fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(notes!),
          ),
        ],
      ],
    );
  }
}
