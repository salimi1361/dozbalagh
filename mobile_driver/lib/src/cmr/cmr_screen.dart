import 'package:flutter/material.dart';

import '../trips/permit_preview_service.dart';
import 'cmr_repository.dart';

class CmrScreen extends StatefulWidget {
  const CmrScreen({required this.token, required this.active, super.key});

  final String token;
  final bool active;

  @override
  State<CmrScreen> createState() => _CmrScreenState();
}

class _CmrScreenState extends State<CmrScreen> {
  final _repository = CmrRepository();
  final _preview = const PermitPreviewService();
  List<DriverCmr> _documents = const [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(covariant CmrScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.active && !oldWidget.active) _load(silent: true);
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent && mounted) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final documents = await _repository.getDocuments(widget.token);
      if (!mounted) return;
      setState(() {
        _documents = documents;
        _loading = false;
        _error = null;
      });
    } on CmrException catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = error.message;
      });
    }
  }

  Future<void> _openPrint(DriverCmr document) async {
    if (document.printUrl.isEmpty) return;
    await _preview.open(endpoint: document.printUrl, token: widget.token);
  }

  Future<void> _runAction(DriverCmr document) async {
    final reservation = TextEditingController();
    final consignee = TextEditingController();
    final action = document.status == 'issued' ? 'accept' : document.status == 'accepted' ? 'start' : 'deliver';
    final confirmed = await showDialog<bool>(context: context, builder: (context) => AlertDialog(
      title: Text(action == 'accept' ? 'تأیید تحویل گرفتن کالا' : action == 'start' ? 'شروع حمل' : 'ثبت تحویل به گیرنده'),
      content: Column(mainAxisSize: MainAxisSize.min, children: [
        if (action == 'deliver') TextField(controller: consignee, textDirection: TextDirection.ltr, decoration: const InputDecoration(labelText: 'نام لاتین تحویل‌گیرنده *')),
        if (action != 'start') TextField(controller: reservation, textDirection: TextDirection.ltr, maxLines: 2, decoration: const InputDecoration(labelText: 'ملاحظه یا مغایرت (اختیاری)')),
        const SizedBox(height: 12),
        Text(action == 'deliver' ? 'گیرنده باید متن و نسخه فعلی CMR را روی همین دستگاه مشاهده و تأیید کند.' : 'این عملیات همراه زمان، نسخه و هش سند ثبت می‌شود.'),
      ]),
      actions: [TextButton(onPressed: ()=>Navigator.pop(context,false), child: const Text('انصراف')), FilledButton(onPressed: ()=>Navigator.pop(context,true), child: const Text('تأیید'))],
    ));
    if (confirmed != true) return;
    if (action == 'deliver' && consignee.text.trim().isEmpty) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('نام لاتین تحویل‌گیرنده الزامی است.')));
      return;
    }
    try {
      await _repository.updateStatus(widget.token, document.id, action, data: action == 'accept'
        ? {'confirmed': true, 'reservation': reservation.text.trim()}
        : action == 'deliver'
          ? {'confirmed_by_consignee': true, 'consignee_signer_name': consignee.text.trim(), 'reservation': reservation.text.trim()}
          : const {});
      await _load(silent: true);
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('عملیات CMR با موفقیت ثبت شد.')));
    } on CmrException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      reservation.dispose(); consignee.dispose();
    }
  }

  @override
  Widget build(BuildContext context) => RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const Text('e-CMRهای من', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900)),
            const SizedBox(height: 6),
            const Text(
              'نسخه الکترونیکی و چاپی اسناد حمل تخصیص‌یافته به شما',
              style: TextStyle(color: Colors.black54),
            ),
            const SizedBox(height: 18),
            if (_loading)
              const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator()))
            else if (_error != null)
              _MessageCard(message: _error!, onRetry: _load)
            else if (_documents.isEmpty)
              const _EmptyCard()
            else
              ..._documents.map(
                (document) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  document.companySerial.isNotEmpty ? document.companySerial : document.number,
                                  style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w900),
                                ),
                              ),
                              Chip(label: Text(_status(document.status))),
                            ],
                          ),
                          const Divider(),
                          Text(document.companyName, style: const TextStyle(fontWeight: FontWeight.w800)),
                          const SizedBox(height: 8),
                          Text(
                            '${document.takingOverPlace} → ${document.deliveryPlace}',
                            textDirection: TextDirection.ltr,
                          ),
                          const SizedBox(height: 12),
                          SizedBox(
                            width: double.infinity,
                            child: FilledButton.icon(
                              onPressed: () => _openPrint(document),
                              icon: const Icon(Icons.description_outlined),
                              label: const Text('مشاهده نسخه چاپی CMR'),
                            ),
                          ),
                          if (['issued','accepted','in_transit'].contains(document.status)) ...[
                            const SizedBox(height: 8),
                            SizedBox(width: double.infinity, child: OutlinedButton.icon(
                              onPressed: () => _runAction(document),
                              icon: Icon(document.status == 'issued' ? Icons.task_alt : document.status == 'accepted' ? Icons.local_shipping_outlined : Icons.inventory_2_outlined),
                              label: Text(document.status == 'issued' ? 'تأیید دریافت کالا' : document.status == 'accepted' ? 'شروع حمل' : 'ثبت تحویل به گیرنده'),
                            )),
                          ],
                        ],
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      );

  static String _status(String status) => switch (status) {
        'issued' => 'صادرشده',
        'accepted' => 'پذیرفته‌شده',
        'in_transit' => 'در مسیر',
        'delivered' => 'تحویل‌شده',
        'finalized' => 'نهایی',
        'cancelled' => 'لغوشده',
        _ => status,
      };
}

class _EmptyCard extends StatelessWidget {
  const _EmptyCard();

  @override
  Widget build(BuildContext context) => const Card(
        child: Padding(
          padding: EdgeInsets.all(28),
          child: Center(child: Text('هنوز CMR صادرشده‌ای برای شما وجود ندارد.')),
        ),
      );
}

class _MessageCard extends StatelessWidget {
  const _MessageCard({required this.message, required this.onRetry});

  final String message;
  final Future<void> Function({bool silent}) onRetry;

  @override
  Widget build(BuildContext context) => Card(
        color: const Color(0xFFE11D48).withValues(alpha: .08),
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            children: [
              Text(message),
              TextButton(onPressed: () => onRetry(), child: const Text('تلاش دوباره')),
            ],
          ),
        ),
      );
}
