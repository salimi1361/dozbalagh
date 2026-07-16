import 'dart:async';

import 'package:flutter/material.dart';

import '../notifications/push_notification_service.dart';
import 'startup_announcement_service.dart';

class StartupAnnouncementGate extends StatefulWidget {
  const StartupAnnouncementGate({
    required this.apiToken,
    required this.child,
    super.key,
  });

  final String apiToken;
  final Widget child;

  @override
  State<StartupAnnouncementGate> createState() =>
      _StartupAnnouncementGateState();
}

class _StartupAnnouncementGateState extends State<StartupAnnouncementGate> {
  final StartupAnnouncementService _service = StartupAnnouncementService();
  StreamSubscription<void>? _pushSubscription;
  List<StartupAnnouncement> _items = const [];
  bool _checking = true;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _pushSubscription = PushNotificationService.instance.announcementRequests
        .listen((_) => _load());
    PushNotificationService.instance.takePendingAnnouncement();
    _load();
  }

  @override
  void didUpdateWidget(covariant StartupAnnouncementGate oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.apiToken != widget.apiToken) _load();
  }

  @override
  void dispose() {
    _pushSubscription?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    final items = await _service.fetch(widget.apiToken);
    if (!mounted) return;
    setState(() {
      _items = items;
      _checking = false;
    });
  }

  Future<void> _confirm() async {
    if (_submitting || _items.isEmpty) return;
    setState(() => _submitting = true);
    final item = _items.first;
    final success = item.requiresAcknowledgement
        ? await _service.acknowledge(widget.apiToken, item.id)
        : await _service.markSeen(widget.apiToken, item.id);
    if (!mounted) return;
    if (success) {
      setState(() {
        _items = _items.skip(1).toList();
        _submitting = false;
      });
    } else {
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('ثبت مشاهده انجام نشد؛ اتصال اینترنت را بررسی کنید.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_checking || _items.isEmpty) return widget.child;
    final item = _items.first;
    final urgent = item.priority == 'urgent' || item.displayMode == 'emergency';
    return PopScope(
      canPop: false,
      child: Scaffold(
        backgroundColor: const Color(0xFFF4F7FB),
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 520),
                child: Card(
                  elevation: 0,
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Icon(
                          urgent
                              ? Icons.notification_important_rounded
                              : Icons.campaign_rounded,
                          size: 64,
                          color: urgent ? Colors.red : const Color(0xFF087F5B),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          item.title,
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.headlineSmall
                              ?.copyWith(fontWeight: FontWeight.w900),
                        ),
                        const SizedBox(height: 18),
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: urgent
                                ? const Color(0xFFFFF1F2)
                                : const Color(0xFFF0FDF4),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Text(
                            item.message,
                            textAlign: TextAlign.right,
                            style: const TextStyle(height: 1.9, fontSize: 15),
                          ),
                        ),
                        if (_items.length > 1) ...[
                          const SizedBox(height: 12),
                          Text(
                            '${_items.length - 1} اطلاعیه دیگر پس از این پیام نمایش داده می‌شود.',
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Colors.black54,
                              fontSize: 12,
                            ),
                          ),
                        ],
                        const SizedBox(height: 20),
                        FilledButton.icon(
                          onPressed: _submitting ? null : _confirm,
                          icon: _submitting
                              ? const SizedBox.square(
                                  dimension: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.check_circle_outline_rounded),
                          label: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            child: Text(
                              item.requiresAcknowledgement
                                  ? item.acknowledgementText
                                  : 'متوجه شدم',
                            ),
                          ),
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
