import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import 'cmr_detail_screen.dart';
import 'cmr_format.dart';
import 'cmr_repository.dart';

class CmrScreen extends StatefulWidget {
  const CmrScreen({
    required this.token,
    required this.active,
    this.requestedCmrId,
    this.openRequestSerial = 0,
    super.key,
  });

  final String token;
  final bool active;
  final int? requestedCmrId;
  final int openRequestSerial;

  @override
  State<CmrScreen> createState() => _CmrScreenState();
}

class _CmrScreenState extends State<CmrScreen> {
  final _repository = CmrRepository();
  List<DriverCmr> _documents = const [];
  bool _loading = true;
  bool _showArchive = false;
  String? _error;
  int _handledOpenRequestSerial = -1;

  @override
  void initState() {
    super.initState();
    _load();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => _openRequestedDocument(),
    );
  }

  @override
  void didUpdateWidget(covariant CmrScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.active &&
        (!oldWidget.active ||
            widget.openRequestSerial != oldWidget.openRequestSerial)) {
      _load(silent: true).then((_) => _openRequestedDocument());
    }
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

  Future<void> _openDocument(DriverCmr document) async {
    final changed = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) =>
            CmrDetailScreen(token: widget.token, cmrId: document.id),
      ),
    );
    if (changed == true) await _load(silent: true);
  }

  Future<void> _openRequestedDocument() async {
    final cmrId = widget.requestedCmrId;
    if (!mounted ||
        !widget.active ||
        cmrId == null ||
        widget.openRequestSerial == _handledOpenRequestSerial) {
      return;
    }
    _handledOpenRequestSerial = widget.openRequestSerial;
    DriverCmr? requested;
    for (final document in _documents) {
      if (document.id == cmrId) {
        requested = document;
        break;
      }
    }
    if (requested == null) {
      try {
        requested = (await _repository.getDocument(
          widget.token,
          cmrId,
        )).summary;
      } on CmrException catch (error) {
        _error = error.message;
        if (mounted) setState(() {});
        return;
      }
    }
    if (mounted) await _openDocument(requested);
  }

  @override
  Widget build(BuildContext context) {
    final activeDocuments = _documents
        .where((document) => document.isActive)
        .toList();
    final archivedDocuments = _documents
        .where((document) => !document.isActive)
        .toList();
    final visible = _showArchive ? archivedDocuments : activeDocuments;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 112),
        children: [
          _CmrHero(
            showArchive: _showArchive,
            activeCount: activeDocuments.length,
            archiveCount: archivedDocuments.length,
            onChanged: (value) => setState(() => _showArchive = value),
          ),
          const SizedBox(height: 16),
          if (_loading)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(36),
                child: CircularProgressIndicator(),
              ),
            )
          else if (_error != null)
            _MessageCard(message: _error!, onRetry: _load)
          else if (visible.isEmpty)
            _EmptyCard(archive: _showArchive)
          else
            ...visible.map(
              (document) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _CmrCard(
                  document: document,
                  onTap: () => _openDocument(document),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _CmrHero extends StatelessWidget {
  const _CmrHero({
    required this.showArchive,
    required this.activeCount,
    required this.archiveCount,
    required this.onChanged,
  });

  final bool showArchive;
  final int activeCount;
  final int archiveCount;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(19),
    decoration: BoxDecoration(
      gradient: const LinearGradient(
        colors: [AppTheme.blue, Color(0xFF079C91)],
        begin: Alignment.topRight,
        end: Alignment.bottomLeft,
      ),
      borderRadius: BorderRadius.circular(26),
      boxShadow: [
        BoxShadow(
          color: AppTheme.blue.withValues(alpha: .18),
          blurRadius: 24,
          offset: const Offset(0, 12),
        ),
      ],
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 58,
              height: 58,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: .16),
                borderRadius: BorderRadius.circular(19),
                border: Border.all(color: Colors.white.withValues(alpha: .26)),
              ),
              child: const Icon(
                Icons.description_rounded,
                color: Colors.white,
                size: 32,
              ),
            ),
            const SizedBox(width: 13),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'CMRهای من',
                    textDirection: TextDirection.rtl,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'مدیریت اسناد حمل بین‌المللی و تحویل بار',
                    style: TextStyle(color: Color(0xFFD8F1FF), fontSize: 10),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: .14),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Text(
                'e-CMR',
                textDirection: TextDirection.ltr,
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 8,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Row(
          children: [
            _CmrHeroCount(
              icon: Icons.description_rounded,
              value: activeCount,
              label: 'سند فعال',
              selected: !showArchive,
              onTap: () => onChanged(false),
            ),
            const SizedBox(width: 9),
            _CmrHeroCount(
              icon: Icons.history_rounded,
              value: archiveCount,
              label: 'بایگانی',
              selected: showArchive,
              onTap: () => onChanged(true),
            ),
          ],
        ),
      ],
    ),
  );
}

class _CmrHeroCount extends StatelessWidget {
  const _CmrHeroCount({
    required this.icon,
    required this.value,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final int value;
  final bool selected;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Expanded(
    child: Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(13),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(13),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
          decoration: BoxDecoration(
            color: selected ? const Color(0x30FFFFFF) : const Color(0x20FFFFFF),
            borderRadius: BorderRadius.circular(13),
            border: Border.all(
              color: selected
                  ? const Color(0x77FFFFFF)
                  : const Color(0x33FFFFFF),
            ),
          ),
          child: Row(
            children: [
              Icon(icon, color: const Color(0xFFB9FFE7), size: 18),
              const SizedBox(width: 5),
              Text(
                CmrFormat.digits('$value'),
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 15,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  label,
                  maxLines: 1,
                  style: const TextStyle(
                    color: Color(0xFFE2F3FF),
                    fontSize: 8,
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

class _CmrCard extends StatelessWidget {
  const _CmrCard({required this.document, required this.onTap});
  final DriverCmr document;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final status = cmrStatusPresentation(document.status);
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: Container(
          padding: const EdgeInsets.all(17),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: const Color(0xFFDCE6EF)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 7,
                    ),
                    decoration: BoxDecoration(
                      color: status.color.withValues(alpha: .11),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(status.icon, size: 16, color: status.color),
                        const SizedBox(width: 5),
                        Text(
                          status.label,
                          style: TextStyle(
                            color: status.color,
                            fontSize: 11,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Spacer(),
                  Text(
                    document.companySerial.isNotEmpty
                        ? document.companySerial
                        : document.number,
                    textDirection: TextDirection.ltr,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                      color: AppTheme.navy,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 13),
              Text(
                document.companyName,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 12,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFFF5F8FB),
                  borderRadius: BorderRadius.circular(15),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: _RoutePoint(
                        icon: Icons.radio_button_checked_rounded,
                        label: 'مبدأ',
                        value: document.takingOverPlace,
                        color: AppTheme.blue,
                      ),
                    ),
                    const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 7),
                      child: Icon(
                        Icons.arrow_back_rounded,
                        color: Color(0xFF8CA0B5),
                        size: 20,
                      ),
                    ),
                    Expanded(
                      child: _RoutePoint(
                        icon: Icons.location_on_rounded,
                        label: 'مقصد',
                        value: document.deliveryPlace,
                        color: AppTheme.green,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  const Icon(
                    Icons.calendar_month_outlined,
                    size: 17,
                    color: Color(0xFF75869A),
                  ),
                  const SizedBox(width: 5),
                  Text(
                    'صدور ${CmrFormat.date(document.issuedAt)}',
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFF75869A),
                    ),
                  ),
                  const Spacer(),
                  const Text(
                    'مشاهده و ادامه فرایند',
                    style: TextStyle(
                      color: AppTheme.blue,
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(width: 3),
                  const Icon(
                    Icons.chevron_left_rounded,
                    size: 20,
                    color: AppTheme.blue,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _RoutePoint extends StatelessWidget {
  const _RoutePoint({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Icon(icon, size: 18, color: color),
      const SizedBox(width: 6),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: const TextStyle(fontSize: 9, color: Color(0xFF8797A8)),
            ),
            Text(
              value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
            ),
          ],
        ),
      ),
    ],
  );
}

class CmrStatusPresentation {
  const CmrStatusPresentation(this.label, this.color, this.icon);
  final String label;
  final Color color;
  final IconData icon;
}

CmrStatusPresentation cmrStatusPresentation(String status) => switch (status) {
  'issued' => const CmrStatusPresentation(
    'نیازمند تحویل‌گیری',
    Color(0xFFE78A00),
    Icons.inventory_2_outlined,
  ),
  'accepted' => const CmrStatusPresentation(
    'آماده شروع حمل',
    AppTheme.blue,
    Icons.task_alt_rounded,
  ),
  'in_transit' => const CmrStatusPresentation(
    'در مسیر',
    Color(0xFF8B5CF6),
    Icons.local_shipping_rounded,
  ),
  'delivered' => const CmrStatusPresentation(
    'منتظر نهایی‌سازی',
    AppTheme.green,
    Icons.inventory_rounded,
  ),
  'finalized' => const CmrStatusPresentation(
    'نهایی‌شده',
    Color(0xFF17795F),
    Icons.verified_rounded,
  ),
  'cancelled' => const CmrStatusPresentation(
    'لغوشده',
    Color(0xFFE11D48),
    Icons.cancel_outlined,
  ),
  _ => CmrStatusPresentation(
    status,
    const Color(0xFF66768A),
    Icons.help_outline_rounded,
  ),
};

class _EmptyCard extends StatelessWidget {
  const _EmptyCard({required this.archive});
  final bool archive;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(32),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(22),
      border: Border.all(color: const Color(0xFFDCE6EF)),
    ),
    child: Column(
      children: [
        Icon(
          archive ? Icons.inventory_2_outlined : Icons.description_outlined,
          size: 45,
          color: const Color(0xFF9AACBD),
        ),
        const SizedBox(height: 12),
        Text(
          archive
              ? 'هنوز سندی در بایگانی نیست.'
              : 'CMR فعالی برای شما صادر نشده است.',
          textAlign: TextAlign.center,
          style: const TextStyle(fontWeight: FontWeight.w800),
        ),
      ],
    ),
  );
}

class _MessageCard extends StatelessWidget {
  const _MessageCard({required this.message, required this.onRetry});
  final String message;
  final Future<void> Function({bool silent}) onRetry;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      color: const Color(0xFFFFF1F3),
      borderRadius: BorderRadius.circular(20),
    ),
    child: Column(
      children: [
        const Icon(Icons.cloud_off_rounded, color: Color(0xFFE11D48), size: 36),
        const SizedBox(height: 10),
        Text(message, textAlign: TextAlign.center),
        TextButton(
          onPressed: () => onRetry(),
          child: const Text('تلاش دوباره'),
        ),
      ],
    ),
  );
}
