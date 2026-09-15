import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../theme/app_theme.dart';
import '../trips/permit_preview_service.dart';
import '../trips/trip_tracking_service.dart';
import 'cmr_format.dart';
import 'cmr_handover_screen.dart';
import 'cmr_repository.dart';

class CmrDetailScreen extends StatefulWidget {
  const CmrDetailScreen({required this.token, required this.cmrId, super.key});

  final String token;
  final int cmrId;

  @override
  State<CmrDetailScreen> createState() => _CmrDetailScreenState();
}

class _CmrDetailScreenState extends State<CmrDetailScreen> {
  final _repository = CmrRepository();
  final _preview = const PermitPreviewService();
  final _tracking = const TripTrackingService();
  CmrDetail? _detail;
  bool _loading = true;
  bool _acting = false;
  bool _changed = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (mounted) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final detail = await _repository.getDocument(widget.token, widget.cmrId);
      if (!mounted) return;
      setState(() {
        _detail = detail;
        _loading = false;
      });
    } on CmrException catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = error.message;
      });
    }
  }

  Future<void> _openPrint() async {
    final url = _detail?.summary.printUrl ?? '';
    if (url.isNotEmpty) await _preview.open(endpoint: url, token: widget.token);
  }

  Future<void> _primaryAction() async {
    final detail = _detail;
    if (detail == null || _acting) return;
    final status = detail.summary.status;
    if (status == 'issued') {
      if (detail.policy.origin.enabled) {
        await _openHandover(detail, 'origin');
      } else {
        await _simpleAccept(detail);
      }
      return;
    }
    if (status == 'accepted') {
      await _startTransport(detail);
      return;
    }
    if (status == 'in_transit') {
      if (detail.policy.destination.enabled) {
        await _openHandover(detail, 'destination');
      } else {
        await _simpleDeliver(detail);
      }
    }
  }

  Future<void> _openHandover(CmrDetail detail, String stage) async {
    final submitted = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => CmrHandoverScreen(
          token: widget.token,
          detail: detail,
          stage: stage,
        ),
      ),
    );
    if (submitted != true) return;
    if (stage == 'destination') await _safeStopTracking();
    _changed = true;
    await _load();
    _message(
      stage == 'origin'
          ? 'تحویل‌گیری بار و شواهد آن ثبت شد.'
          : 'تحویل بار در مقصد با موفقیت ثبت شد.',
    );
  }

  Future<void> _simpleAccept(CmrDetail detail) async {
    final reservation = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('تأیید تحویل‌گیری بار'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'با تأیید این مرحله، دریافت بار و نسخه جاری CMR به نام شما ثبت می‌شود.',
              style: TextStyle(fontSize: 12, height: 1.7),
            ),
            const SizedBox(height: 14),
            TextField(
              controller: reservation,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'ملاحظات یا مغایرت (اختیاری)',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('انصراف'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('تأیید دریافت بار'),
          ),
        ],
      ),
    );
    if (confirmed != true) {
      reservation.dispose();
      return;
    }
    await _run(
      () => _repository.updateStatus(
        widget.token,
        detail.summary.id,
        'accept',
        data: {
          'confirmed': true,
          'reservation': reservation.text.trim(),
          'document_version': detail.version,
          'document_hash': detail.integrityHash,
        },
      ),
      success: 'تحویل‌گیری بار تأیید شد.',
    );
    reservation.dispose();
  }

  Future<void> _startTransport(CmrDetail detail) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        icon: const Icon(Icons.route_rounded, color: AppTheme.green, size: 42),
        title: const Text('شروع حمل و ردیابی'),
        content: const Text(
          'با شروع حمل، موقعیت مسیر در پس‌زمینه برای شرکت ارسال می‌شود و اعلان دائمی ردیابی روی گوشی نمایش داده خواهد شد.',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 12, height: 1.8),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('فعلاً نه'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('شروع حمل'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    setState(() => _acting = true);
    try {
      await _tracking.startCmr(cmrId: detail.summary.id, token: widget.token);
      try {
        await _repository.updateStatus(
          widget.token,
          detail.summary.id,
          'start',
        );
      } catch (_) {
        await _safeStopTracking();
        rethrow;
      }
      _changed = true;
      await _load();
      _message('حمل شروع شد و ردیابی مسیر فعال است.');
    } on CmrException catch (error) {
      _message(_friendlyError(error.message));
    } on PlatformException catch (error) {
      _message(error.message ?? 'فعال‌کردن ردیابی ممکن نشد.');
    } finally {
      if (mounted) setState(() => _acting = false);
    }
  }

  Future<void> _simpleDeliver(CmrDetail detail) async {
    final receiver = TextEditingController();
    final reservation = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('ثبت تحویل به گیرنده'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'گیرنده باید نسخه جاری سند را روی همین گوشی مشاهده و تحویل بار را تأیید کند.',
                style: TextStyle(fontSize: 12, height: 1.7),
              ),
              const SizedBox(height: 14),
              TextField(
                controller: receiver,
                textDirection: TextDirection.ltr,
                decoration: const InputDecoration(
                  labelText: 'نام لاتین گیرنده *',
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: reservation,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: 'ملاحظات تحویل (اختیاری)',
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('انصراف'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('ثبت تحویل'),
          ),
        ],
      ),
    );
    if (confirmed != true) {
      receiver.dispose();
      reservation.dispose();
      return;
    }
    final name = receiver.text.trim();
    if (name.isEmpty || !RegExp(r'^[A-Za-z0-9 .,_/()\-]+$').hasMatch(name)) {
      _message('نام گیرنده را با حروف لاتین وارد کنید.');
      receiver.dispose();
      reservation.dispose();
      return;
    }
    await _run(() async {
      await _repository.updateStatus(
        widget.token,
        detail.summary.id,
        'deliver',
        data: {
          'confirmed_by_consignee': true,
          'consignee_signer_name': name,
          'reservation': reservation.text.trim(),
          'document_version': detail.version,
          'document_hash': detail.integrityHash,
        },
      );
      await _safeStopTracking();
    }, success: 'تحویل بار به گیرنده ثبت شد.');
    receiver.dispose();
    reservation.dispose();
  }

  Future<void> _run(
    Future<void> Function() action, {
    required String success,
  }) async {
    if (_acting) return;
    setState(() => _acting = true);
    try {
      await action();
      _changed = true;
      await _load();
      _message(success);
    } on CmrException catch (error) {
      _message(_friendlyError(error.message));
    } on PlatformException catch (error) {
      _message(error.message ?? 'عملیات روی گوشی کامل نشد.');
    } finally {
      if (mounted) setState(() => _acting = false);
    }
  }

  Future<void> _safeStopTracking() async {
    try {
      await _tracking.stop();
    } catch (_) {}
  }

  void _message(String message) {
    if (mounted) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    }
  }

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: !_changed,
    onPopInvokedWithResult: (didPop, _) {
      if (!didPop && _changed) {
        setState(() => _changed = false);
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) Navigator.of(context).pop(true);
        });
      }
    },
    child: Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        appBar: AppBar(
          backgroundColor: AppTheme.navy,
          foregroundColor: Colors.white,
          title: const Text('جزئیات CMR'),
          actions: [
            IconButton(
              onPressed: _detail == null ? null : _openPrint,
              tooltip: 'نسخه رسمی',
              icon: const Icon(Icons.picture_as_pdf_outlined),
            ),
          ],
        ),
        body: _body(),
        bottomNavigationBar: _actionBar(),
      ),
    ),
  );

  Widget _body() {
    if (_loading && _detail == null) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null && _detail == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(_error!, textAlign: TextAlign.center),
              const SizedBox(height: 8),
              TextButton(onPressed: _load, child: const Text('تلاش دوباره')),
            ],
          ),
        ),
      );
    }
    final detail = _detail!;
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 120),
        children: [
          _Header(detail: detail),
          const SizedBox(height: 14),
          _Process(status: detail.summary.status),
          const SizedBox(height: 14),
          _InfoSection(
            title: 'مسیر و زمان‌بندی',
            icon: Icons.route_rounded,
            children: [
              _InfoRow(
                label: 'مبدأ تحویل بار',
                value: detail.summary.takingOverPlace,
              ),
              _InfoRow(
                label: 'زمان تحویل‌گیری',
                value: CmrFormat.date(
                  detail.summary.takingOverAt,
                  withTime: true,
                ),
              ),
              _InfoRow(
                label: 'مقصد تحویل',
                value: detail.summary.deliveryPlace,
              ),
              _InfoRow(
                label: 'موعد تحویل',
                value: CmrFormat.date(
                  detail.summary.plannedDeliveryAt,
                  withTime: true,
                ),
              ),
              if (detail.summary.vehiclePlate.isNotEmpty)
                _InfoRow(
                  label: 'پلاک ناوگان',
                  value: detail.summary.vehiclePlate,
                ),
            ],
          ),
          const SizedBox(height: 12),
          _InfoSection(
            title: 'طرف‌های سند',
            icon: Icons.groups_2_outlined,
            children: [
              _PartyRow(label: 'فرستنده', party: detail.consignor),
              _PartyRow(label: 'گیرنده', party: detail.consignee),
              _PartyRow(label: 'حمل‌کننده', party: detail.carrier),
            ],
          ),
          const SizedBox(height: 12),
          _GoodsSection(goods: detail.goods),
          if (detail.attachments.isNotEmpty) ...[
            const SizedBox(height: 12),
            _InfoSection(
              title: 'پیوست‌های سند',
              icon: Icons.attach_file_rounded,
              children: detail.attachments
                  .map(
                    (file) => ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const CircleAvatar(
                        child: Icon(Icons.description_outlined),
                      ),
                      title: Text(
                        file.name,
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      subtitle: Text(
                        file.type,
                        style: const TextStyle(fontSize: 10),
                      ),
                    ),
                  )
                  .toList(),
            ),
          ],
          if (detail.handovers.isNotEmpty || detail.signatures.isNotEmpty) ...[
            const SizedBox(height: 12),
            _EvidenceSection(detail: detail),
          ],
          if (detail.events.isNotEmpty) ...[
            const SizedBox(height: 12),
            _Timeline(events: detail.events),
          ],
        ],
      ),
    );
  }

  Widget? _actionBar() {
    final detail = _detail;
    if (detail == null ||
        !const [
          'issued',
          'accepted',
          'in_transit',
        ].contains(detail.summary.status)) {
      return null;
    }
    final data = switch (detail.summary.status) {
      'issued' => (
        Icons.inventory_2_outlined,
        'ثبت تحویل‌گیری بار در مبدأ',
        AppTheme.green,
      ),
      'accepted' => (
        Icons.play_circle_fill_rounded,
        'شروع حمل و ردیابی مسیر',
        AppTheme.blue,
      ),
      _ => (Icons.fact_check_outlined, 'ثبت تحویل بار در مقصد', AppTheme.green),
    };
    return SafeArea(
      child: Container(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
        decoration: const BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              color: Color(0x18000000),
              blurRadius: 16,
              offset: Offset(0, -4),
            ),
          ],
        ),
        child: FilledButton.icon(
          onPressed: _acting ? null : _primaryAction,
          style: FilledButton.styleFrom(
            backgroundColor: data.$3,
            minimumSize: const Size.fromHeight(54),
          ),
          icon: _acting
              ? const SizedBox(
                  width: 19,
                  height: 19,
                  child: CircularProgressIndicator(
                    strokeWidth: 2.2,
                    color: Colors.white,
                  ),
                )
              : Icon(data.$1),
          label: Text(_acting ? 'در حال ثبت عملیات…' : data.$2),
        ),
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.detail});
  final CmrDetail detail;

  @override
  Widget build(BuildContext context) {
    final status = _status(detail.summary.status);
    return Container(
      padding: const EdgeInsets.all(19),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppTheme.blue, Color(0xFF079C91)],
        ),
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .16),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.description_rounded,
                  color: Colors.white,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  detail.summary.companySerial.isNotEmpty
                      ? detail.summary.companySerial
                      : detail.summary.number,
                  textDirection: TextDirection.ltr,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 17,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Text(
                  status,
                  style: const TextStyle(
                    color: AppTheme.navy,
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            detail.summary.companyName,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 14,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 7),
          Text(
            'نسخه ${CmrFormat.digits('${detail.version}')}  •  صدور ${CmrFormat.date(detail.summary.issuedAt)}',
            style: TextStyle(
              color: Colors.white.withValues(alpha: .8),
              fontSize: 11,
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              const Icon(
                Icons.verified_user_outlined,
                color: Color(0xFF9BF1D7),
                size: 17,
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  detail.integrityHash.isEmpty
                      ? 'شناسه اعتبار سند ثبت نشده'
                      : 'نسخه جاری سند دارای مهر یکپارچگی است',
                  style: const TextStyle(
                    color: Color(0xFFD7FFF3),
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Process extends StatelessWidget {
  const _Process({required this.status});
  final String status;

  @override
  Widget build(BuildContext context) {
    final current = const [
      'issued',
      'accepted',
      'in_transit',
      'delivered',
      'finalized',
    ].indexOf(status);
    const labels = ['صدور', 'تحویل‌گیری', 'شروع حمل', 'تحویل مقصد', 'نهایی'];
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFDCE6EF)),
      ),
      child: Row(
        children: List.generate(
          labels.length,
          (index) => Expanded(
            child: Column(
              children: [
                Row(
                  children: [
                    if (index > 0)
                      Expanded(
                        child: Container(
                          height: 2,
                          color: index <= current
                              ? AppTheme.green
                              : const Color(0xFFD9E2EB),
                        ),
                      ),
                    Container(
                      width: 25,
                      height: 25,
                      decoration: BoxDecoration(
                        color: index <= current
                            ? AppTheme.green
                            : const Color(0xFFE8EEF4),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        index < current ? Icons.check_rounded : Icons.circle,
                        size: index < current ? 15 : 7,
                        color: index <= current
                            ? Colors.white
                            : const Color(0xFF9AACBD),
                      ),
                    ),
                    if (index < labels.length - 1)
                      Expanded(
                        child: Container(
                          height: 2,
                          color: index < current
                              ? AppTheme.green
                              : const Color(0xFFD9E2EB),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  labels[index],
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 8,
                    fontWeight: FontWeight.w800,
                    color: index <= current
                        ? AppTheme.navy
                        : const Color(0xFF9AACBD),
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

class _InfoSection extends StatelessWidget {
  const _InfoSection({
    required this.title,
    required this.icon,
    required this.children,
  });
  final String title;
  final IconData icon;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      border: Border.all(color: const Color(0xFFDCE6EF)),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, color: AppTheme.blue, size: 21),
            const SizedBox(width: 7),
            Text(
              title,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900),
            ),
          ],
        ),
        const SizedBox(height: 12),
        ...children,
      ],
    ),
  );
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});
  final String label;
  final String value;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 6),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 105,
          child: Text(
            label,
            style: const TextStyle(color: Color(0xFF75869A), fontSize: 11),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
          ),
        ),
      ],
    ),
  );
}

class _PartyRow extends StatelessWidget {
  const _PartyRow({required this.label, required this.party});
  final String label;
  final CmrParty party;
  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 8),
    padding: const EdgeInsets.all(11),
    decoration: BoxDecoration(
      color: const Color(0xFFF5F8FB),
      borderRadius: BorderRadius.circular(13),
    ),
    child: Row(
      children: [
        SizedBox(
          width: 67,
          child: Text(
            label,
            style: const TextStyle(
              color: AppTheme.blue,
              fontSize: 10,
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                party.name,
                textDirection: TextDirection.ltr,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                ),
              ),
              if (party.address.isNotEmpty)
                Text(
                  party.address,
                  textDirection: TextDirection.ltr,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 9, color: Color(0xFF75869A)),
                ),
            ],
          ),
        ),
      ],
    ),
  );
}

class _GoodsSection extends StatelessWidget {
  const _GoodsSection({required this.goods});
  final List<CmrGood> goods;
  @override
  Widget build(BuildContext context) => _InfoSection(
    title: 'مشخصات کالا',
    icon: Icons.inventory_2_outlined,
    children: goods.isEmpty
        ? [
            const Text(
              'جزئیات کالا ثبت نشده است.',
              style: TextStyle(fontSize: 11),
            ),
          ]
        : goods
              .map(
                (good) => Container(
                  margin: const EdgeInsets.only(bottom: 9),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF5F8FB),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        good.description,
                        textDirection: TextDirection.ltr,
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 7),
                      Wrap(
                        spacing: 7,
                        runSpacing: 7,
                        children: [
                          if (good.packageCount.isNotEmpty)
                            _Tag('تعداد ${good.packageCount}'),
                          if (good.packageType.isNotEmpty)
                            _Tag(good.packageType),
                          if (good.grossWeightKg.isNotEmpty)
                            _Tag('${good.grossWeightKg} kg'),
                          if (good.volumeM3.isNotEmpty)
                            _Tag('${good.volumeM3} m³'),
                          if (good.unNumber.isNotEmpty)
                            _Tag('UN ${good.unNumber}', alert: true),
                          if (good.adrClass.isNotEmpty)
                            _Tag('ADR ${good.adrClass}', alert: true),
                        ],
                      ),
                    ],
                  ),
                ),
              )
              .toList(),
  );
}

class _Tag extends StatelessWidget {
  const _Tag(this.text, {this.alert = false});
  final String text;
  final bool alert;
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
    decoration: BoxDecoration(
      color: alert ? const Color(0xFFFFE9D9) : Colors.white,
      borderRadius: BorderRadius.circular(9),
    ),
    child: Text(
      text,
      textDirection: TextDirection.ltr,
      style: TextStyle(
        fontSize: 9,
        fontWeight: FontWeight.w700,
        color: alert ? const Color(0xFFB64D00) : const Color(0xFF55677A),
      ),
    ),
  );
}

class _EvidenceSection extends StatelessWidget {
  const _EvidenceSection({required this.detail});
  final CmrDetail detail;
  @override
  Widget build(BuildContext context) => _InfoSection(
    title: 'تأییدها و شواهد',
    icon: Icons.verified_user_outlined,
    children: [
      ...detail.signatures.map(
        (item) => ListTile(
          contentPadding: EdgeInsets.zero,
          dense: true,
          leading: const CircleAvatar(
            backgroundColor: Color(0xFFE9F9F3),
            child: Icon(Icons.draw_rounded, color: AppTheme.green, size: 19),
          ),
          title: Text(
            '${_role(item.role)} — ${item.name}',
            textDirection: TextDirection.ltr,
            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
          ),
          subtitle: Text(
            CmrFormat.date(item.signedAt, withTime: true),
            style: const TextStyle(fontSize: 9),
          ),
        ),
      ),
      ...detail.handovers.map(
        (item) => ListTile(
          contentPadding: EdgeInsets.zero,
          dense: true,
          leading: const CircleAvatar(
            backgroundColor: Color(0xFFEAF3FF),
            child: Icon(
              Icons.fact_check_outlined,
              color: AppTheme.blue,
              size: 19,
            ),
          ),
          title: Text(
            '${item.stage == 'origin' ? 'مبدأ' : 'مقصد'} — ${_outcome(item.outcome)}',
            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
          ),
          subtitle: Text(
            item.notes.isEmpty
                ? CmrFormat.date(item.occurredAt, withTime: true)
                : item.notes,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 9),
          ),
        ),
      ),
    ],
  );
}

class _Timeline extends StatelessWidget {
  const _Timeline({required this.events});
  final List<CmrTimelineEvent> events;
  @override
  Widget build(BuildContext context) => _InfoSection(
    title: 'تاریخچه عملیات',
    icon: Icons.history_rounded,
    children: events
        .map(
          (event) => Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 4),
                  width: 9,
                  height: 9,
                  decoration: const BoxDecoration(
                    color: AppTheme.green,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 9),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _event(event.type),
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      if (event.description.isNotEmpty)
                        Text(
                          event.description,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 9,
                            color: Color(0xFF75869A),
                          ),
                        ),
                      Text(
                        CmrFormat.date(event.occurredAt, withTime: true),
                        style: const TextStyle(
                          fontSize: 9,
                          color: Color(0xFF91A0AF),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        )
        .toList(),
  );
}

String _status(String value) => switch (value) {
  'issued' => 'صادرشده',
  'accepted' => 'تحویل‌گرفته‌شده',
  'in_transit' => 'در مسیر',
  'delivered' => 'تحویل‌شده',
  'finalized' => 'نهایی‌شده',
  'cancelled' => 'لغوشده',
  _ => value,
};
String _role(String value) => switch (value) {
  'driver' => 'راننده',
  'sender' => 'فرستنده',
  'consignee' => 'گیرنده',
  _ => value,
};
String _outcome(String value) => switch (value) {
  'accepted' => 'تحویل کامل',
  'accepted_with_reservations' => 'تحویل با شرط',
  'delivered' => 'تحویل کامل',
  'partial' => 'تحویل ناقص',
  'damaged' => 'خسارت‌دیده',
  'refused' => 'امتناع',
  _ => value,
};
String _event(String value) => switch (value) {
  'issued' => 'صدور سند',
  'accepted' => 'تحویل‌گیری بار',
  'transport_started' => 'شروع حمل',
  'delivered' => 'تحویل در مقصد',
  'finalized' => 'نهایی‌سازی سند',
  'handover_origin' => 'شواهد مبدأ',
  'handover_destination' => 'شواهد مقصد',
  _ => value,
};

String _friendlyError(String message) {
  if (message.contains('changed') || message.contains('Refresh')) {
    return 'سند CMR تغییر کرده است؛ صفحه را به‌روزرسانی و دوباره بررسی کنید.';
  }
  if (message.contains('origin handover')) {
    return 'برای این شرکت ثبت شواهد تحویل‌گیری مبدأ الزامی است.';
  }
  if (message.contains('destination handover')) {
    return 'برای این شرکت ثبت شواهد تحویل مقصد الزامی است.';
  }
  return message;
}
