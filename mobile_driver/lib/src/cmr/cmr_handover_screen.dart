import 'dart:io';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';

import '../theme/app_theme.dart';
import 'cmr_evidence_service.dart';
import 'cmr_repository.dart';

class CmrHandoverScreen extends StatefulWidget {
  const CmrHandoverScreen({
    required this.token,
    required this.detail,
    required this.stage,
    super.key,
  });

  final String token;
  final CmrDetail detail;
  final String stage;

  @override
  State<CmrHandoverScreen> createState() => _CmrHandoverScreenState();
}

class _CmrHandoverScreenState extends State<CmrHandoverScreen> {
  final _repository = CmrRepository();
  final _evidence = const CmrEvidenceService();
  final _signatureKey = GlobalKey();
  final _signerName = TextEditingController();
  final _signerIdentifier = TextEditingController();
  final _notes = TextEditingController();
  final List<Offset?> _signaturePoints = [];
  final List<String> _photos = [];
  late final String _clientUuid;
  late String _outcome;
  CmrEvidenceLocation? _location;
  bool _confirmed = false;
  bool _submitting = false;
  bool _gettingLocation = false;
  bool _openingCamera = false;

  bool get _origin => widget.stage == 'origin';
  CmrStagePolicy get _policy =>
      _origin ? widget.detail.policy.origin : widget.detail.policy.destination;

  @override
  void initState() {
    super.initState();
    _clientUuid = newCmrClientUuid();
    _outcome = _origin ? 'accepted' : 'delivered';
  }

  @override
  void dispose() {
    _signerName.dispose();
    _signerIdentifier.dispose();
    _notes.dispose();
    for (final path in _photos) {
      runCatchingDelete(path);
    }
    super.dispose();
  }

  List<(String, String)> get _outcomes {
    if (_origin) {
      return [
        const ('accepted', 'تحویل کامل و بدون مغایرت'),
        if (widget.detail.policy.allowExceptions)
          const ('accepted_with_reservations', 'تحویل با شرط یا مغایرت'),
        if (widget.detail.policy.allowExceptions)
          const ('refused', 'امتناع از تحویل‌گیری'),
      ];
    }
    return [
      const ('delivered', 'تحویل کامل به گیرنده'),
      if (widget.detail.policy.allowExceptions)
        const ('partial', 'تحویل ناقص یا کسری'),
      if (widget.detail.policy.allowExceptions)
        const ('damaged', 'تحویل کالای خسارت‌دیده'),
      if (widget.detail.policy.allowExceptions)
        const ('refused', 'امتناع گیرنده از دریافت'),
    ];
  }

  bool get _exception => const [
    'accepted_with_reservations',
    'partial',
    'damaged',
    'refused',
  ].contains(_outcome);
  bool get _photoRequired =>
      _policy.requirePhoto ||
      const ['partial', 'damaged', 'refused'].contains(_outcome);

  Future<void> _capturePhoto() async {
    if (_photos.length >= 5 || _openingCamera) return;
    setState(() => _openingCamera = true);
    try {
      final path = await _evidence.capturePhoto();
      if (path != null && mounted) setState(() => _photos.add(path));
    } on PlatformException catch (error) {
      _message(error.message ?? 'بازکردن دوربین ممکن نشد.');
    } finally {
      if (mounted) setState(() => _openingCamera = false);
    }
  }

  Future<void> _getLocation() async {
    if (_gettingLocation) return;
    setState(() => _gettingLocation = true);
    try {
      final location = await _evidence.getCurrentLocation();
      if (mounted) setState(() => _location = location);
    } on PlatformException catch (error) {
      _message(error.message ?? 'موقعیت دقیق دریافت نشد.');
    } finally {
      if (mounted) setState(() => _gettingLocation = false);
    }
  }

  Future<Uint8List?> _signaturePng() async {
    if (_signaturePoints.whereType<Offset>().isEmpty) return null;
    final boundary =
        _signatureKey.currentContext?.findRenderObject()
            as RenderRepaintBoundary?;
    final image = await boundary?.toImage(pixelRatio: 2.2);
    final data = await image?.toByteData(format: ui.ImageByteFormat.png);
    return data?.buffer.asUint8List();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (_policy.requireSignature && _signerName.text.trim().isEmpty) {
      _message('نام امضاکننده الزامی است.');
      return;
    }
    final signature = await _signaturePng();
    if (_policy.requireSignature && signature == null) {
      _message('امضای روی صفحه الزامی است.');
      return;
    }
    if (_photoRequired && _photos.isEmpty) {
      _message('حداقل یک عکس از وضعیت بار یا تحویل ثبت کنید.');
      return;
    }
    if (_policy.requireGps && _location == null) {
      _message('ثبت موقعیت GPS برای این مرحله الزامی است.');
      return;
    }
    if (_exception && _notes.text.trim().isEmpty) {
      _message('برای مغایرت، خسارت، کسری یا امتناع توضیحات الزامی است.');
      return;
    }
    if (!_confirmed) {
      _message('تأیید صحت اطلاعات و مشاهده نسخه جاری سند الزامی است.');
      return;
    }
    setState(() => _submitting = true);
    try {
      await _repository.submitHandover(
        widget.token,
        widget.detail.summary.id,
        CmrHandoverPayload(
          clientUuid: _clientUuid,
          stage: widget.stage,
          outcome: _outcome,
          documentVersion: widget.detail.version,
          documentHash: widget.detail.integrityHash,
          confirmed: true,
          signerName: _signerName.text,
          signerIdentifier: _signerIdentifier.text,
          notes: _notes.text,
          latitude: _location?.latitude,
          longitude: _location?.longitude,
          accuracy: _location?.accuracy,
          signature: signature,
          photos: _photos,
        ),
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } on CmrException catch (error) {
      _message(_friendlyError(error.message));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  void _message(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) => Directionality(
    textDirection: TextDirection.rtl,
    child: Scaffold(
      appBar: AppBar(
        backgroundColor: AppTheme.navy,
        foregroundColor: Colors.white,
        title: Text(_origin ? 'تحویل‌گیری بار در مبدأ' : 'تحویل بار در مقصد'),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 120),
        children: [
          _DocumentBanner(detail: widget.detail, origin: _origin),
          const SizedBox(height: 18),
          _Section(
            icon: Icons.rule_rounded,
            title: 'نتیجه این مرحله',
            required: true,
            child: Column(
              children: _outcomes.map((item) {
                final selected = item.$1 == _outcome;
                return ListTile(
                  onTap: () => setState(() => _outcome = item.$1),
                  contentPadding: EdgeInsets.zero,
                  dense: true,
                  leading: Icon(
                    selected
                        ? Icons.radio_button_checked_rounded
                        : Icons.radio_button_off_rounded,
                    color: selected ? AppTheme.blue : const Color(0xFF9AACBD),
                  ),
                  title: Text(
                    item.$2,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 12),
          _Section(
            icon: Icons.badge_outlined,
            title: _origin ? 'مشخصات تحویل‌دهنده' : 'مشخصات گیرنده',
            required: _policy.requireSignature,
            child: Column(
              children: [
                TextField(
                  controller: _signerName,
                  textDirection: TextDirection.ltr,
                  decoration: InputDecoration(
                    labelText: _origin ? 'نام تحویل‌دهنده' : 'نام گیرنده',
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _signerIdentifier,
                  textDirection: TextDirection.ltr,
                  decoration: const InputDecoration(
                    labelText: 'شناسه، کد یا شماره مدرک (اختیاری)',
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _Section(
            icon: Icons.draw_rounded,
            title: 'امضای روی گوشی',
            required: _policy.requireSignature,
            child: Column(
              children: [
                RepaintBoundary(
                  key: _signatureKey,
                  child: GestureDetector(
                    onPanStart: (details) => setState(
                      () => _signaturePoints.add(details.localPosition),
                    ),
                    onPanUpdate: (details) => setState(
                      () => _signaturePoints.add(details.localPosition),
                    ),
                    onPanEnd: (_) => setState(() => _signaturePoints.add(null)),
                    child: Container(
                      height: 150,
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(15),
                        border: Border.all(color: const Color(0xFFCFDAE5)),
                      ),
                      child: CustomPaint(
                        painter: _SignaturePainter(_signaturePoints),
                        child: _signaturePoints.whereType<Offset>().isEmpty
                            ? const Center(
                                child: Text(
                                  'امضا را در این کادر رسم کنید',
                                  style: TextStyle(
                                    color: Color(0xFF9AA9B8),
                                    fontSize: 12,
                                  ),
                                ),
                              )
                            : null,
                      ),
                    ),
                  ),
                ),
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton.icon(
                    onPressed: () => setState(_signaturePoints.clear),
                    icon: const Icon(Icons.refresh_rounded, size: 18),
                    label: const Text('پاک‌کردن امضا'),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _Section(
            icon: Icons.photo_camera_outlined,
            title: 'عکس‌های شواهد',
            required: _photoRequired,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (_photos.isNotEmpty)
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: List.generate(
                      _photos.length,
                      (index) => Stack(
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: Image.file(
                              File(_photos[index]),
                              width: 86,
                              height: 86,
                              fit: BoxFit.cover,
                            ),
                          ),
                          Positioned(
                            top: 3,
                            left: 3,
                            child: InkWell(
                              onTap: () {
                                final path = _photos.removeAt(index);
                                runCatchingDelete(path);
                                setState(() {});
                              },
                              child: Container(
                                padding: const EdgeInsets.all(3),
                                decoration: const BoxDecoration(
                                  color: Color(0xCC07111F),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.close,
                                  color: Colors.white,
                                  size: 15,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                if (_photos.isNotEmpty) const SizedBox(height: 10),
                OutlinedButton.icon(
                  onPressed: _photos.length >= 5 || _openingCamera
                      ? null
                      : _capturePhoto,
                  icon: _openingCamera
                      ? const SizedBox(
                          width: 17,
                          height: 17,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.add_a_photo_outlined),
                  label: Text(
                    _photos.isEmpty
                        ? 'گرفتن عکس'
                        : 'افزودن عکس دیگر (${_photos.length}/۵)',
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _Section(
            icon: Icons.gps_fixed_rounded,
            title: 'موقعیت محل تحویل',
            required: _policy.requireGps,
            child: _location == null
                ? OutlinedButton.icon(
                    onPressed: _gettingLocation ? null : _getLocation,
                    icon: _gettingLocation
                        ? const SizedBox(
                            width: 17,
                            height: 17,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.my_location_rounded),
                    label: Text(
                      _gettingLocation
                          ? 'در حال دریافت موقعیت دقیق…'
                          : 'ثبت موقعیت فعلی',
                    ),
                  )
                : Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFE9F9F3),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.check_circle_rounded,
                          color: AppTheme.green,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'موقعیت ثبت شد — دقت ${_location!.accuracy.toStringAsFixed(0)} متر',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                        TextButton(
                          onPressed: _getLocation,
                          child: const Text('به‌روزرسانی'),
                        ),
                      ],
                    ),
                  ),
          ),
          const SizedBox(height: 12),
          _Section(
            icon: Icons.notes_rounded,
            title: 'توضیحات و مغایرت‌ها',
            required: _exception,
            child: TextField(
              controller: _notes,
              minLines: 3,
              maxLines: 5,
              decoration: const InputDecoration(
                hintText: 'شرح وضعیت بسته‌ها، پلمب، کسری، آسیب یا علت امتناع',
              ),
            ),
          ),
          const SizedBox(height: 12),
          Container(
            decoration: BoxDecoration(
              color: const Color(0xFFEAF3FF),
              borderRadius: BorderRadius.circular(18),
            ),
            child: CheckboxListTile(
              value: _confirmed,
              onChanged: (value) => setState(() => _confirmed = value == true),
              title: const Text(
                'نسخه جاری CMR را مشاهده کردم و صحت اطلاعات ثبت‌شده را تأیید می‌کنم.',
                style: TextStyle(
                  fontSize: 12,
                  height: 1.6,
                  fontWeight: FontWeight.w800,
                ),
              ),
              controlAffinity: ListTileControlAffinity.leading,
            ),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
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
            onPressed: _submitting ? null : _submit,
            style: FilledButton.styleFrom(
              backgroundColor: AppTheme.green,
              minimumSize: const Size.fromHeight(54),
            ),
            icon: _submitting
                ? const SizedBox(
                    width: 19,
                    height: 19,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.verified_rounded),
            label: Text(
              _submitting
                  ? 'در حال ثبت امن شواهد…'
                  : (_origin ? 'ثبت تحویل‌گیری در مبدأ' : 'ثبت تحویل در مقصد'),
            ),
          ),
        ),
      ),
    ),
  );
}

class _DocumentBanner extends StatelessWidget {
  const _DocumentBanner({required this.detail, required this.origin});
  final CmrDetail detail;
  final bool origin;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      gradient: const LinearGradient(
        colors: [AppTheme.navy, Color(0xFF12395B)],
      ),
      borderRadius: BorderRadius.circular(22),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.description_rounded, color: Color(0xFF5DE5BB)),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                detail.summary.companySerial.isNotEmpty
                    ? detail.summary.companySerial
                    : detail.summary.number,
                textDirection: TextDirection.ltr,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
            Text(
              'نسخه ${detail.version}',
              style: const TextStyle(color: Color(0xFFB8C8D8), fontSize: 11),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Text(
          origin
              ? '${detail.summary.takingOverPlace} — تحویل‌گیری بار'
              : '${detail.summary.deliveryPlace} — تحویل به گیرنده',
          style: const TextStyle(
            color: Colors.white,
            fontSize: 13,
            fontWeight: FontWeight.w800,
          ),
        ),
      ],
    ),
  );
}

class _Section extends StatelessWidget {
  const _Section({
    required this.icon,
    required this.title,
    required this.child,
    this.required = false,
  });
  final IconData icon;
  final String title;
  final Widget child;
  final bool required;

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
            Icon(icon, size: 20, color: AppTheme.blue),
            const SizedBox(width: 7),
            Text(
              title,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900),
            ),
            if (required)
              const Text(
                '  *',
                style: TextStyle(
                  color: Color(0xFFE11D48),
                  fontWeight: FontWeight.w900,
                ),
              ),
          ],
        ),
        const SizedBox(height: 14),
        child,
      ],
    ),
  );
}

class _SignaturePainter extends CustomPainter {
  const _SignaturePainter(this.points);
  final List<Offset?> points;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = AppTheme.navy
      ..strokeCap = StrokeCap.round
      ..strokeWidth = 2.6;
    for (var index = 0; index < points.length - 1; index++) {
      final current = points[index];
      final next = points[index + 1];
      if (current != null && next != null) {
        canvas.drawLine(current, next, paint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant _SignaturePainter oldDelegate) => true;
}

void runCatchingDelete(String path) {
  try {
    final file = File(path);
    if (file.existsSync()) file.deleteSync();
  } catch (_) {}
}

String _friendlyError(String message) {
  if (message.contains('changed') || message.contains('Refresh')) {
    return 'سند CMR تغییر کرده است؛ صفحه را به‌روزرسانی و دوباره بررسی کنید.';
  }
  if (message.contains('Signature')) {
    return 'نام و امضای تحویل‌دهنده یا گیرنده الزامی است.';
  }
  if (message.contains('photo')) {
    return 'حداقل یک عکس شواهد برای این وضعیت الزامی است.';
  }
  if (message.contains('GPS')) {
    return 'ثبت موقعیت GPS برای این مرحله الزامی است.';
  }
  if (message.contains('Notes')) {
    return 'ثبت توضیحات مغایرت یا امتناع الزامی است.';
  }
  return message;
}
