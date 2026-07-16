import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../auth/auth_repository.dart';
import '../security/security_service.dart';
import '../theme/app_theme.dart';
import '../widgets/iran_plate.dart';
import '../widgets/app_version_label.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({
    required this.session,
    required this.onLock,
    required this.onLogout,
    super.key,
  });

  final DriverSession session;
  final Future<void> Function() onLock;
  final Future<void> Function() onLogout;

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final _security = SecurityService();
  final _authRepository = AuthRepository();
  bool _biometricAvailable = false;
  bool _biometricEnabled = false;
  bool _hasPin = false;
  bool _loadingSecurity = true;

  @override
  void initState() {
    super.initState();
    _loadSecurity();
  }

  Future<void> _loadSecurity() async {
    final results = await Future.wait([
      _security.isBiometricAvailable(),
      _security.isBiometricEnabled(),
      _security.hasPin(),
    ]);
    if (!mounted) return;
    setState(() {
      _biometricAvailable = results[0];
      _biometricEnabled = results[1];
      _hasPin = results[2];
      _loadingSecurity = false;
    });
  }

  Future<void> _toggleBiometric(bool enabled) async {
    final changed = await _security.setBiometricEnabled(enabled);
    if (!mounted) return;
    setState(() => _biometricEnabled = changed ? enabled : _biometricEnabled);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          changed
              ? enabled
                    ? 'ورود با اثر انگشت فعال شد.'
                    : 'ورود با اثر انگشت غیرفعال شد.'
              : 'اثر انگشت تأیید نشد و تنظیمات تغییر نکرد.',
        ),
      ),
    );
  }

  Future<void> _changePin() async {
    final current = TextEditingController();
    final next = TextEditingController();
    final repeat = TextEditingController();
    String? error;
    final saved = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: Text(
            _hasPin ? 'تغییر رمز ورود برنامه' : 'ایجاد رمز ورود برنامه',
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (_hasPin) ...[
                _PinField(controller: current, label: 'رمز فعلی'),
                const SizedBox(height: 10),
              ],
              _PinField(controller: next, label: 'رمز جدید ۴ تا ۶ رقمی'),
              const SizedBox(height: 10),
              _PinField(controller: repeat, label: 'تکرار رمز جدید'),
              if (error != null) ...[
                const SizedBox(height: 10),
                Text(error!, style: const TextStyle(color: Colors.red)),
              ],
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('انصراف'),
            ),
            FilledButton(
              onPressed: () async {
                if (_hasPin && !await _security.verifyPin(current.text)) {
                  setDialogState(() => error = 'رمز فعلی صحیح نیست.');
                  return;
                }
                if (!RegExp(r'^\d{4,6}$').hasMatch(next.text)) {
                  setDialogState(() => error = 'رمز باید ۴ تا ۶ رقم باشد.');
                  return;
                }
                if (next.text != repeat.text) {
                  setDialogState(
                    () => error = 'تکرار رمز با رمز جدید یکسان نیست.',
                  );
                  return;
                }
                try {
                  await _authRepository.saveDevicePin(
                    widget.session,
                    next.text,
                  );
                  await _security.savePin(next.text);
                  if (context.mounted) Navigator.pop(context, true);
                } on AuthException catch (exception) {
                  setDialogState(() => error = exception.message);
                }
              },
              child: const Text('ذخیره'),
            ),
          ],
        ),
      ),
    );
    current.dispose();
    next.dispose();
    repeat.dispose();
    if (saved == true && mounted) {
      setState(() => _hasPin = true);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('رمز ورود برنامه ذخیره شد.')),
      );
    }
  }

  Future<void> _showDetails({
    required String title,
    required IconData icon,
    required List<(String, String)> rows,
    String? plate,
  }) {
    return showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) => Directionality(
        textDirection: TextDirection.rtl,
        child: Container(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 30),
          decoration: const BoxDecoration(
            color: Color(0xFFF8FAFC),
            borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
          ),
          child: SafeArea(
            top: false,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 42,
                  height: 4,
                  decoration: BoxDecoration(
                    color: const Color(0xFFCBD5E1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    CircleAvatar(
                      backgroundColor: const Color(0xFFEAF4FF),
                      foregroundColor: AppTheme.blue,
                      child: Icon(icon),
                    ),
                    const SizedBox(width: 11),
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ],
                ),
                if (plate != null) ...[
                  const SizedBox(height: 22),
                  IranPlate(plate: plate),
                ],
                const SizedBox(height: 18),
                ...rows.map(
                  (row) => Container(
                    margin: const EdgeInsets.only(bottom: 9),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 13,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(15),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Row(
                      children: [
                        Text(
                          row.$1,
                          style: const TextStyle(color: Color(0xFF64748B)),
                        ),
                        const Spacer(),
                        Flexible(
                          child: Text(
                            row.$2,
                            textAlign: TextAlign.left,
                            style: const TextStyle(fontWeight: FontWeight.w900),
                          ),
                        ),
                      ],
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

  Future<void> _showAbout() => showDialog<void>(
    context: context,
    builder: (context) => AlertDialog(
      icon: const Icon(Icons.local_shipping_rounded, color: AppTheme.blue),
      title: const Text('دوزوله راننده'),
      content: const Text(
        'اپلیکیشن همراه رانندگان ترانزیت بین‌المللی برای ارتباط با شرکت، مدیریت سفرها و خدمات هوشمند آینده.',
        textAlign: TextAlign.center,
        style: TextStyle(height: 1.8),
      ),
      actionsAlignment: MainAxisAlignment.center,
      actions: [
        FilledButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('متوجه شدم'),
        ),
      ],
    ),
  );

  Future<void> _confirmLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        icon: const Icon(Icons.logout_rounded, color: Color(0xFFE11D48)),
        title: const Text('خروج کامل از دستگاه'),
        content: const Text(
          'نشست ورود از این گوشی حذف می‌شود و برای ورود بعدی باید دوباره کد پیامکی دریافت کنید. ادامه می‌دهید؟',
          textAlign: TextAlign.center,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('انصراف'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFFE11D48),
            ),
            child: const Text('خروج'),
          ),
        ],
      ),
    );
    if (confirmed == true) await widget.onLogout();
  }

  @override
  Widget build(BuildContext context) {
    final driver = widget.session.driver;
    String value(String key) {
      final result = driver[key]?.toString().trim();
      return result == null || result.isEmpty ? 'ثبت نشده' : result;
    }

    final mobile = value('mobile');
    final plate = value('truck_plate');
    return ColoredBox(
      color: const Color(0xFFF3F7FB),
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 16, 14, 32),
        children: [
          _ProfileHero(
            name: widget.session.name,
            mobile: mobile,
            companyName: widget.session.companyName,
          ),
          const SizedBox(height: 22),
          const _SectionTitle(title: 'ناوگان متصل'),
          const SizedBox(height: 8),
          _FleetCard(
            plate: plate,
            truckType: value('truck_type'),
            smartCard: value('truck_smart_card'),
            onTap: () => _showDetails(
              title: 'اطلاعات ناوگان',
              icon: Icons.local_shipping_rounded,
              plate: plate,
              rows: [
                ('نوع ناوگان', value('truck_type')),
                ('کارت هوشمند', value('truck_smart_card')),
              ],
            ),
          ),
          const SizedBox(height: 22),
          const _SectionTitle(title: 'تنظیمات امنیتی'),
          const SizedBox(height: 8),
          _SettingsGroup(
            children: [
              _SettingsTile(
                icon: Icons.fingerprint_rounded,
                title: 'ورود با اثر انگشت',
                subtitle: _biometricAvailable
                    ? 'ورود سریع و امن بدون تایپ رمز'
                    : 'اثر انگشت روی این دستگاه در دسترس نیست',
                trailing: Switch(
                  value: _biometricEnabled,
                  activeTrackColor: AppTheme.green,
                  inactiveThumbColor: Colors.white,
                  inactiveTrackColor: const Color(0xFFD6DEE8),
                  onChanged: _loadingSecurity || !_biometricAvailable
                      ? null
                      : _toggleBiometric,
                ),
              ),
              _SettingsTile(
                icon: Icons.key_rounded,
                title: _hasPin ? 'تغییر رمز ورود' : 'ایجاد رمز ورود',
                subtitle: 'رمز ۴ تا ۶ رقمی مخصوص همین گوشی',
                onTap: _changePin,
              ),
            ],
          ),
          const SizedBox(height: 22),
          const _SectionTitle(title: 'حساب و اطلاعات'),
          const SizedBox(height: 8),
          _SettingsGroup(
            children: [
              _SettingsTile(
                icon: Icons.badge_outlined,
                title: 'اطلاعات راننده',
                subtitle: '$mobile  •  کد ملی ${value('national_code')}',
                onTap: () => _showDetails(
                  title: 'اطلاعات راننده',
                  icon: Icons.badge_rounded,
                  rows: [
                    ('نام و نام خانوادگی', widget.session.name),
                    ('کد ملی', value('national_code')),
                    ('شماره موبایل', mobile),
                  ],
                ),
              ),
              _SettingsTile(
                icon: Icons.apartment_rounded,
                title: 'شرکت متصل',
                subtitle: widget.session.companyName,
                onTap: () => _showDetails(
                  title: 'اطلاعات شرکت',
                  icon: Icons.apartment_rounded,
                  rows: [
                    ('نام شرکت', widget.session.companyName),
                    ('مدیرعامل', value('company_manager')),
                    ('تلفن', value('company_phone')),
                    ('نشانی', value('company_address')),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 22),
          const _SectionTitle(title: 'عمومی'),
          const SizedBox(height: 8),
          _SettingsGroup(
            children: [
              _SettingsTile(
                icon: Icons.info_outline_rounded,
                title: 'درباره دوزوله راننده',
                subtitle: 'معرفی اپلیکیشن و خدمات همراه راننده',
                onTap: _showAbout,
              ),
              _SettingsTile(
                icon: Icons.lock_rounded,
                title: 'قفل و خروج از برنامه',
                subtitle: 'ورود بعدی با رمز یا اثر انگشت؛ بدون پیامک',
                onTap: widget.onLock,
              ),
              _SettingsTile(
                icon: Icons.phonelink_erase_rounded,
                title: 'خروج کامل از این دستگاه',
                subtitle: 'حذف نشست؛ ورود بعدی با کد پیامکی',
                danger: true,
                onTap: _confirmLogout,
              ),
            ],
          ),
          const SizedBox(height: 18),
          const Center(
            child: Column(
              children: [
                Text(
                  'دوزوله • همراه هوشمند رانندگان',
                  style: TextStyle(color: Color(0xFF64748B), fontSize: 9),
                ),
                SizedBox(height: 4),
                AppVersionLabel(),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PinField extends StatelessWidget {
  const _PinField({required this.controller, required this.label});
  final TextEditingController controller;
  final String label;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      keyboardType: TextInputType.number,
      obscureText: true,
      maxLength: 6,
      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
      decoration: InputDecoration(labelText: label, counterText: ''),
    );
  }
}

class _ProfileHero extends StatelessWidget {
  const _ProfileHero({
    required this.name,
    required this.mobile,
    required this.companyName,
  });

  final String name;
  final String mobile;
  final String companyName;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [AppTheme.blue, Color(0xFF0D9488)],
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: const [
          BoxShadow(
            color: Color(0x241974C8),
            blurRadius: 22,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Row(
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                width: 66,
                height: 66,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: const LinearGradient(
                    colors: [Color(0xFF32D6A0), Color(0xFF21A8FF)],
                  ),
                  border: Border.all(color: Colors.white, width: 2.5),
                ),
                child: Text(
                  name.trim().isEmpty ? 'ر' : name.trim()[0],
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 25,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              Positioned(
                left: -2,
                bottom: -2,
                child: Container(
                  width: 24,
                  height: 24,
                  decoration: BoxDecoration(
                    color: const Color(0xFF0F172A),
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 1.5),
                  ),
                  child: const Icon(
                    Icons.local_shipping_rounded,
                    size: 14,
                    color: Color(0xFF78F5D0),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(width: 13),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  mobile,
                  textDirection: TextDirection.ltr,
                  style: const TextStyle(
                    color: Color(0xFFDDF8F5),
                    fontSize: 11,
                  ),
                ),
                const SizedBox(height: 7),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 9,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0x24FFFFFF),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0x55FFFFFF)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.verified_rounded,
                        size: 13,
                        color: Colors.white,
                      ),
                      const SizedBox(width: 4),
                      Flexible(
                        child: Text(
                          companyName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 8,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const Icon(Icons.shield_rounded, color: Colors.white, size: 25),
        ],
      ),
    );
  }
}

class _FleetCard extends StatelessWidget {
  const _FleetCard({
    required this.plate,
    required this.truckType,
    required this.smartCard,
    required this.onTap,
  });

  final String plate;
  final String truckType;
  final String smartCard;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: Container(
          padding: const EdgeInsets.fromLTRB(14, 16, 14, 14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: const Color(0xFFE1EAF3)),
            boxShadow: const [
              BoxShadow(
                color: Color(0x0F0F2742),
                blurRadius: 16,
                offset: Offset(0, 6),
              ),
            ],
          ),
          child: Column(
            children: [
              Center(
                child: FittedBox(child: IranPlate(plate: plate)),
              ),
              const SizedBox(height: 13),
              Row(
                children: [
                  const Icon(
                    Icons.local_shipping_outlined,
                    color: AppTheme.green,
                    size: 20,
                  ),
                  const SizedBox(width: 7),
                  Expanded(
                    child: Text(
                      truckType == 'ثبت نشده'
                          ? 'نوع ناوگان ثبت نشده'
                          : truckType,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Color(0xFF122033),
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  Text(
                    smartCard == 'ثبت نشده'
                        ? 'جزئیات ناوگان'
                        : 'کارت $smartCard',
                    style: const TextStyle(
                      color: Color(0xFF718096),
                      fontSize: 9,
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Icon(
                    Icons.chevron_left_rounded,
                    color: Color(0xFF94A3B8),
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

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 6),
      child: Row(
        children: [
          Container(
            width: 4,
            height: 17,
            decoration: BoxDecoration(
              color: AppTheme.green,
              borderRadius: BorderRadius.circular(4),
            ),
          ),
          const SizedBox(width: 7),
          Text(
            title,
            style: const TextStyle(
              color: Color(0xFF122033),
              fontSize: 13,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _SettingsGroup extends StatelessWidget {
  const _SettingsGroup({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE1EAF3)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0D0F2742),
            blurRadius: 15,
            offset: Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        children: [
          for (var index = 0; index < children.length; index++) ...[
            children[index],
            if (index < children.length - 1)
              const Divider(
                height: 1,
                thickness: 1,
                indent: 72,
                endIndent: 14,
                color: Color(0xFFEDF2F7),
              ),
          ],
        ],
      ),
    );
  }
}

class _SettingsTile extends StatelessWidget {
  const _SettingsTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    this.trailing,
    this.onTap,
    this.danger = false,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Widget? trailing;
  final VoidCallback? onTap;
  final bool danger;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        child: Container(
          constraints: const BoxConstraints(minHeight: 68),
          padding: const EdgeInsets.fromLTRB(12, 10, 14, 10),
          child: Row(
            children: [
              Container(
                width: 45,
                height: 45,
                decoration: BoxDecoration(
                  color: danger
                      ? const Color(0xFFFFEEF1)
                      : const Color(0xFFEAF5FB),
                  borderRadius: BorderRadius.circular(15),
                ),
                child: Icon(
                  icon,
                  color: danger ? const Color(0xFFE24A67) : AppTheme.blue,
                  size: 23,
                ),
              ),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        color: danger
                            ? const Color(0xFFC93653)
                            : const Color(0xFF172235),
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Color(0xFF7B8796),
                        fontSize: 9,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 6),
              trailing ??
                  Icon(
                    Icons.chevron_left_rounded,
                    color: danger
                        ? const Color(0xFFE24A67)
                        : const Color(0xFF94A3B8),
                  ),
            ],
          ),
        ),
      ),
    );
  }
}
