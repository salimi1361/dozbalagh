import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../assistant/assistant_speech_service.dart';
import '../auth/auth_repository.dart';
import '../chat/company_chat_repository.dart';
import '../chat/company_chat_screen.dart';
import '../cmr/cmr_screen.dart';
import '../notifications/push_notification_service.dart';
import '../settings/settings_screen.dart';
import '../theme/app_theme.dart';
import '../trips/permit_repository.dart';
import '../trips/permit_preview_service.dart';
import '../trips/trip_tracking_service.dart';
import '../widgets/iran_plate.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({
    required this.session,
    required this.onLock,
    required this.onLogout,
    super.key,
  });

  final DriverSession session;
  final Future<void> Function() onLock;
  final Future<void> Function() onLogout;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with WidgetsBindingObserver {
  final _chatRepository = CompanyChatRepository();
  final _permitRepository = PermitRepository();
  final _tripTrackingService = const TripTrackingService();
  final _permitPreviewService = const PermitPreviewService();
  int _selectedIndex = 0;
  int _unreadCount = 0;
  bool _checkingUnread = false;
  bool _permitsLoading = true;
  bool _permitsRefreshing = false;
  String? _permitsError;
  List<DriverPermit> _permits = const [];
  int? _activeTrackingItemId;
  bool _trackingBusy = false;
  Timer? _unreadTimer;
  Timer? _permitsTimer;
  StreamSubscription<int>? _pushNavigationSubscription;
  int? _requestedCompanyId;
  int _chatOpenRequestSerial = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _pushNavigationSubscription = PushNotificationService
        .instance
        .companyNavigation
        .listen(_openCompanyConversation);
    final pendingCompanyId = PushNotificationService.instance
        .takePendingCompanyId();
    if (pendingCompanyId != null) {
      WidgetsBinding.instance.addPostFrameCallback(
        (_) => _openCompanyConversation(pendingCompanyId),
      );
    }
    _pollUnread();
    _loadPermits();
    _restoreTrackingState();
    _unreadTimer = Timer.periodic(
      const Duration(seconds: 3),
      (_) => _pollUnread(),
    );
    _permitsTimer = Timer.periodic(
      const Duration(minutes: 2),
      (_) => _loadPermits(silent: true),
    );
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _pollUnread();
      _loadPermits(silent: true);
      _restoreTrackingState();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _unreadTimer?.cancel();
    _permitsTimer?.cancel();
    _pushNavigationSubscription?.cancel();
    super.dispose();
  }

  Future<void> _pollUnread() async {
    if (_checkingUnread) return;
    _checkingUnread = true;
    try {
      final conversations = await _chatRepository.getConversations(
        widget.session.token,
      );
      final total = conversations.fold<int>(
        0,
        (sum, conversation) => sum + conversation.unreadCount,
      );
      if (!mounted) return;
      setState(() {
        _unreadCount = total;
      });
    } on CompanyChatException {
      // خطای موقت شبکه نباید صفحه اصلی را مختل کند.
    } finally {
      _checkingUnread = false;
    }
  }

  void _setUnreadCount(int count) {
    if (!mounted || count == _unreadCount) return;
    setState(() {
      _unreadCount = count;
    });
  }

  void _openCompanyConversation(int companyId) {
    if (!mounted) return;
    setState(() {
      _selectedIndex = 4;
      _requestedCompanyId = companyId;
      _chatOpenRequestSerial++;
    });
  }

  Future<void> _loadPermits({bool silent = false}) async {
    if (_permitsRefreshing) return;
    _permitsRefreshing = true;
    if (!silent && mounted) {
      setState(() {
        _permitsLoading = true;
        _permitsError = null;
      });
    }
    try {
      final permits = await _permitRepository.getPermits(widget.session.token);
      if (!mounted) return;
      setState(() {
        _permits = permits;
        _permitsLoading = false;
        _permitsError = null;
      });
    } on PermitException catch (error) {
      if (mounted && !silent) {
        setState(() {
          _permitsLoading = false;
          _permitsError = error.message;
        });
      }
    } finally {
      _permitsRefreshing = false;
    }
  }

  Future<void> _restoreTrackingState() async {
    try {
      final itemId = await _tripTrackingService.getActiveItemId();
      if (!mounted || itemId == _activeTrackingItemId) return;
      setState(() => _activeTrackingItemId = itemId);
    } on PlatformException {
      // وضعیت ردیابی در اجرای بعدی دوباره بررسی می‌شود.
    }
  }

  Future<void> _startTrip(DriverPermit permit) async {
    final trackingItemId = permit.trackingItemId;
    if (trackingItemId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'شناسه ردیابی این دوزوله هنوز از سامانه دریافت نشده است.',
          ),
        ),
      );
      return;
    }
    if (_activeTrackingItemId != null &&
        _activeTrackingItemId != trackingItemId) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('ابتدا سفر فعال فعلی را پایان دهید.')),
      );
      return;
    }
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('شروع سفر و ردیابی'),
        content: Text(
          'با شروع سفر دوزوله ${permit.serialNumber}، موقعیت شما به‌صورت زنده برای شرکت صادرکننده روی نقشه ارسال می‌شود. ادامه می‌دهید؟',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('انصراف'),
          ),
          FilledButton.icon(
            onPressed: () => Navigator.pop(dialogContext, true),
            icon: const Icon(Icons.play_arrow_rounded),
            label: const Text('شروع سفر'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    setState(() => _trackingBusy = true);
    try {
      await _tripTrackingService.start(
        itemId: trackingItemId,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() => _activeTrackingItemId = trackingItemId);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('سفر شروع شد و ارسال زنده موقعیت به شرکت فعال است.'),
          backgroundColor: AppTheme.green,
        ),
      );
    } on PlatformException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message ?? 'شروع ردیابی انجام نشد.')),
      );
    } finally {
      if (mounted) setState(() => _trackingBusy = false);
    }
  }

  Future<void> _stopTrip(DriverPermit permit) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('پایان سفر'),
        content: Text(
          'آیا سفر دوزوله ${permit.serialNumber} پایان یافته است؟ با تأیید، ارسال موقعیت برای شرکت متوقف می‌شود.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('ادامه سفر'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFFE11D48),
            ),
            child: const Text('پایان سفر'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    setState(() => _trackingBusy = true);
    try {
      await _tripTrackingService.stop();
      if (!mounted) return;
      setState(() => _activeTrackingItemId = null);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('سفر پایان یافت و ردیابی متوقف شد.')),
      );
    } on PlatformException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message ?? 'توقف ردیابی انجام نشد.')),
      );
    } finally {
      if (mounted) setState(() => _trackingBusy = false);
    }
  }

  Future<void> _logout() async {
    if (_activeTrackingItemId != null) {
      try {
        await _tripTrackingService.stop();
      } on PlatformException {
        // خروج از حساب نباید به‌خاطر خطای توقف سرویس ناموفق بماند.
      }
    }
    await widget.onLogout();
  }

  Future<void> _openPermitCopy(DriverPermit permit) async {
    final endpoint = permit.printCopyEndpoint;
    if (endpoint == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('تصویر چاپی این دوزوله هنوز در سامانه آماده نشده است.'),
        ),
      );
      return;
    }
    try {
      await _permitPreviewService.open(
        endpoint: endpoint,
        token: widget.session.token,
      );
    } on PlatformException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.message ?? 'نمایش تصویر دوزوله انجام نشد.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final activePermits = _permits.where((permit) => permit.isActive).toList();
    final tripHistory = _permits.where((permit) => !permit.isActive).toList();
    final pages = [
      _DashboardTab(
        session: widget.session,
        activePermits: activePermits,
        historyCount: tripHistory.length,
        loading: _permitsLoading,
        error: _permitsError,
        onRefresh: _loadPermits,
        activeTrackingItemId: _activeTrackingItemId,
        trackingBusy: _trackingBusy,
        onStartTrip: _startTrip,
        onStopTrip: _stopTrip,
        onOpenPermitCopy: _openPermitCopy,
      ),
      _TripsTab(
        permits: tripHistory,
        loading: _permitsLoading,
        error: _permitsError,
        onRefresh: _loadPermits,
        onOpenPermitCopy: _openPermitCopy,
      ),
      _AssistantTab(
        session: widget.session,
        activePermits: activePermits,
        unreadCount: _unreadCount,
        activeTrackingItemId: _activeTrackingItemId,
        loading: _permitsLoading,
        error: _permitsError,
        onRefresh: () async {
          await Future.wait([_loadPermits(), _pollUnread()]);
        },
        onOpenPermits: () => setState(() => _selectedIndex = 0),
        onOpenChats: () => setState(() => _selectedIndex = 4),
      ),
      CmrScreen(token: widget.session.token, active: _selectedIndex == 3),
      CompanyChatScreen(
        session: widget.session,
        active: _selectedIndex == 4,
        onUnreadChanged: _setUnreadCount,
        requestedCompanyId: _requestedCompanyId,
        openRequestSerial: _chatOpenRequestSerial,
      ),
      SettingsScreen(
        session: widget.session,
        onLock: widget.onLock,
        onLogout: _logout,
      ),
    ];
    const titles = [
      'داشبورد راننده',
      'سفرهای من',
      'دستیار هوشمند',
      'e-CMRهای من',
      'گفتگوها',
      'تنظیمات',
    ];

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        appBar: AppBar(
          backgroundColor: AppTheme.navy,
          foregroundColor: Colors.white,
          automaticallyImplyLeading: false,
          title: Text(
            titles[_selectedIndex],
            style: const TextStyle(fontWeight: FontWeight.w900),
          ),
        ),
        body: IndexedStack(index: _selectedIndex, children: pages),
        bottomNavigationBar: NavigationBar(
          height: 76,
          labelTextStyle: const WidgetStatePropertyAll(
            TextStyle(fontSize: 9, fontWeight: FontWeight.w800),
          ),
          selectedIndex: _selectedIndex,
          onDestinationSelected: (index) =>
              setState(() => _selectedIndex = index),
          destinations: [
            const NavigationDestination(
              icon: Icon(Icons.home_outlined),
              selectedIcon: Icon(Icons.home_rounded),
              label: 'خانه',
            ),
            const NavigationDestination(
              icon: Icon(Icons.route_outlined),
              selectedIcon: Icon(Icons.route_rounded),
              label: 'سفرها',
            ),
            const NavigationDestination(
              icon: _AiOrb(size: 48),
              selectedIcon: _AiOrb(size: 52, selected: true),
              label: 'دستیار',
            ),
            const NavigationDestination(
              icon: Icon(Icons.description_outlined),
              selectedIcon: Icon(Icons.description_rounded),
              label: 'CMR',
            ),
            NavigationDestination(
              icon: _ChatNavIcon(unreadCount: _unreadCount),
              selectedIcon: _ChatNavIcon(
                unreadCount: _unreadCount,
                selected: true,
              ),
              label: 'گفتگوها',
            ),
            const NavigationDestination(
              icon: Icon(Icons.settings_outlined),
              selectedIcon: Icon(Icons.settings_rounded),
              label: 'تنظیمات',
            ),
          ],
        ),
      ),
    );
  }
}

class _ChatNavIcon extends StatelessWidget {
  const _ChatNavIcon({required this.unreadCount, this.selected = false});

  final int unreadCount;
  final bool selected;

  @override
  Widget build(BuildContext context) {
    final icon = Icon(
      selected ? Icons.forum_rounded : Icons.forum_outlined,
      color: unreadCount > 0 ? const Color(0xFFFF7A00) : null,
    );
    if (unreadCount <= 0) return icon;
    return Badge.count(
      count: unreadCount,
      backgroundColor: const Color(0xFFE11D48),
      child: icon,
    );
  }
}

class _DashboardTab extends StatelessWidget {
  const _DashboardTab({
    required this.session,
    required this.activePermits,
    required this.historyCount,
    required this.loading,
    required this.error,
    required this.onRefresh,
    required this.activeTrackingItemId,
    required this.trackingBusy,
    required this.onStartTrip,
    required this.onStopTrip,
    required this.onOpenPermitCopy,
  });

  final DriverSession session;
  final List<DriverPermit> activePermits;
  final int historyCount;
  final bool loading;
  final String? error;
  final Future<void> Function({bool silent}) onRefresh;
  final int? activeTrackingItemId;
  final bool trackingBusy;
  final Future<void> Function(DriverPermit permit) onStartTrip;
  final Future<void> Function(DriverPermit permit) onStopTrip;
  final Future<void> Function(DriverPermit permit) onOpenPermitCopy;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(18),
        children: [
          Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppTheme.blue, Color(0xFF0D9488)],
              ),
              borderRadius: BorderRadius.circular(24),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x331168D8),
                  blurRadius: 24,
                  offset: Offset(0, 12),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(
                      Icons.verified_rounded,
                      color: Color(0xFFB9FFE7),
                    ),
                    const SizedBox(width: 8),
                    const Expanded(
                      child: Text(
                        'راننده فعال سامانه',
                        style: TextStyle(
                          color: Color(0xFFCEFFEF),
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    _DashboardDate(date: DateTime.now()),
                  ],
                ),
                const SizedBox(height: 18),
                Text(
                  session.name,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 23,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  session.companyName,
                  style: const TextStyle(
                    color: Color(0xFFE2F3FF),
                    fontSize: 12,
                    height: 1.6,
                  ),
                ),
                const SizedBox(height: 17),
                Row(
                  children: [
                    _DashboardCount(
                      icon: Icons.local_shipping_rounded,
                      value: activePermits.length,
                      label: 'دوزوله فعال',
                    ),
                    const SizedBox(width: 9),
                    _DashboardCount(
                      icon: Icons.history_rounded,
                      value: historyCount,
                      label: 'سابقه سفر',
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 21),
          Row(
            children: [
              const Expanded(
                child: Text(
                  'دوزوله‌های فعال',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.w900),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
                decoration: BoxDecoration(
                  color: const Color(0xFFE8F4FF),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.schedule_rounded,
                      size: 14,
                      color: AppTheme.blue,
                    ),
                    SizedBox(width: 4),
                    Text(
                      'به‌روزرسانی خودکار هر ۲ دقیقه',
                      style: TextStyle(
                        color: AppTheme.blue,
                        fontSize: 8,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (activeTrackingItemId != null) ...[
            Container(
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
              decoration: BoxDecoration(
                color: const Color(0xFFE8FFF6),
                borderRadius: BorderRadius.circular(15),
                border: Border.all(color: const Color(0xFF9EE9D0)),
              ),
              child: const Row(
                children: [
                  Icon(Icons.gps_fixed_rounded, color: AppTheme.green),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'سفر فعال است؛ موقعیت شما به‌صورت زنده برای شرکت ارسال می‌شود.',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
          if (loading)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 42),
              child: Center(child: CircularProgressIndicator()),
            )
          else if (error != null)
            _PermitError(message: error!, onRetry: onRefresh)
          else if (activePermits.isEmpty)
            const _EmptyPermits(
              icon: Icons.task_alt_rounded,
              title: 'دوزوله فعالی ندارید',
              subtitle:
                  'دوزوله جدید پس از صدور شرکت در این بخش نمایش داده می‌شود.',
            )
          else
            ...activePermits.map(
              (permit) => Padding(
                padding: const EdgeInsets.only(bottom: 11),
                child: _PermitCard(
                  permit: permit,
                  active: true,
                  tracking:
                      permit.trackingItemId != null &&
                      permit.trackingItemId == activeTrackingItemId,
                  anotherTripActive:
                      activeTrackingItemId != null &&
                      permit.trackingItemId != activeTrackingItemId,
                  trackingBusy: trackingBusy,
                  onStartTrip: () => onStartTrip(permit),
                  onStopTrip: () => onStopTrip(permit),
                  onOpenPermitCopy: () => onOpenPermitCopy(permit),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _DashboardDate extends StatelessWidget {
  const _DashboardDate({required this.date});

  final DateTime date;

  @override
  Widget build(BuildContext context) {
    final jalali = _DashboardCalendar.fromGregorian(date);
    final weekDay = _DashboardCalendar.weekDays[date.weekday - 1];
    final jalaliDay = _DashboardCalendar.persianDigits('${jalali.$3}');
    final jalaliYear = _DashboardCalendar.persianDigits('${jalali.$1}');
    final jalaliMonth = _DashboardCalendar.jalaliMonths[jalali.$2 - 1];
    final gregorianDate =
        '${date.day} ${_DashboardCalendar.gregorianMonths[date.month - 1].toUpperCase()} ${date.year}';

    return Container(
      width: 126,
      padding: const EdgeInsets.fromLTRB(9, 8, 9, 9),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [
            Colors.white.withValues(alpha: .18),
            Colors.white.withValues(alpha: .08),
          ],
        ),
        borderRadius: BorderRadius.circular(17),
        border: Border.all(color: Colors.white.withValues(alpha: .32)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x1A00162D),
            blurRadius: 10,
            offset: Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              const Icon(
                Icons.calendar_month_rounded,
                size: 14,
                color: Color(0xFFB9FFE7),
              ),
              const SizedBox(width: 5),
              Expanded(
                child: Text(
                  weekDay,
                  style: const TextStyle(
                    color: Color(0xFFE7FFF7),
                    fontSize: 10.5,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 5),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                jalaliDay,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 27,
                  height: 1,
                  fontWeight: FontWeight.w900,
                ),
              ),
              Container(
                width: 1,
                height: 28,
                margin: const EdgeInsets.symmetric(horizontal: 8),
                color: Colors.white.withValues(alpha: .24),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    jalaliMonth,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      height: 1.1,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    jalaliYear,
                    style: const TextStyle(
                      color: Color(0xFFCDEBFF),
                      fontSize: 9.5,
                      height: 1,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 7),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(vertical: 3),
            decoration: BoxDecoration(
              color: const Color(0xFF062A55).withValues(alpha: .2),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              gregorianDate,
              textAlign: TextAlign.center,
              textDirection: TextDirection.ltr,
              style: const TextStyle(
                color: Color(0xFFE0F3FF),
                fontSize: 8.5,
                fontWeight: FontWeight.w700,
                letterSpacing: .35,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _DashboardCalendar {
  const _DashboardCalendar._();

  static const weekDays = [
    'دوشنبه',
    'سه‌شنبه',
    'چهارشنبه',
    'پنجشنبه',
    'جمعه',
    'شنبه',
    'یکشنبه',
  ];

  static const jalaliMonths = [
    'فروردین',
    'اردیبهشت',
    'خرداد',
    'تیر',
    'مرداد',
    'شهریور',
    'مهر',
    'آبان',
    'آذر',
    'دی',
    'بهمن',
    'اسفند',
  ];

  static const gregorianMonths = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];

  static (int, int, int) fromGregorian(DateTime date) {
    const monthDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var gy = date.year - 1600;
    final gm = date.month - 1;
    final gd = date.day - 1;
    var dayNumber =
        365 * gy + ((gy + 3) ~/ 4) - ((gy + 99) ~/ 100) + ((gy + 399) ~/ 400);
    for (var index = 0; index < gm; index++) {
      dayNumber += monthDays[index];
    }
    if (gm > 1 && ((gy % 4 == 0 && gy % 100 != 0) || gy % 400 == 0)) {
      dayNumber++;
    }
    dayNumber += gd;

    var jalaliDayNumber = dayNumber - 79;
    final cycles = jalaliDayNumber ~/ 12053;
    jalaliDayNumber %= 12053;
    var jy = 979 + (33 * cycles) + (4 * (jalaliDayNumber ~/ 1461));
    jalaliDayNumber %= 1461;
    if (jalaliDayNumber >= 366) {
      jy += (jalaliDayNumber - 1) ~/ 365;
      jalaliDayNumber = (jalaliDayNumber - 1) % 365;
    }
    final jm = jalaliDayNumber < 186
        ? 1 + (jalaliDayNumber ~/ 31)
        : 7 + ((jalaliDayNumber - 186) ~/ 30);
    final jd =
        1 +
        (jalaliDayNumber < 186
            ? jalaliDayNumber % 31
            : (jalaliDayNumber - 186) % 30);
    return (jy, jm, jd);
  }

  static String persianDigits(String value) {
    const english = '0123456789';
    const persian = '۰۱۲۳۴۵۶۷۸۹';
    return value.split('').map((char) {
      final index = english.indexOf(char);
      return index < 0 ? char : persian[index];
    }).join();
  }
}

class _TripsTab extends StatelessWidget {
  const _TripsTab({
    required this.permits,
    required this.loading,
    required this.error,
    required this.onRefresh,
    required this.onOpenPermitCopy,
  });

  final List<DriverPermit> permits;
  final bool loading;
  final String? error;
  final Future<void> Function({bool silent}) onRefresh;
  final Future<void> Function(DriverPermit permit) onOpenPermitCopy;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
        children: [
          Container(
            padding: const EdgeInsets.all(17),
            decoration: BoxDecoration(
              color: const Color(0xFF0F2036),
              borderRadius: BorderRadius.circular(22),
            ),
            child: Row(
              children: [
                const CircleAvatar(
                  backgroundColor: Color(0xFF1D4E74),
                  foregroundColor: Color(0xFF78F5D0),
                  child: Icon(Icons.route_rounded),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'سوابق دوزوله و سفر',
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      Text(
                        'درخواست‌ها و سفرهایی که دیگر فعال نیستند',
                        style: TextStyle(
                          color: Color(0xFF94A3B8),
                          fontSize: 10,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0x2232D6A0),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '${permits.length}',
                    style: const TextStyle(
                      color: Color(0xFF78F5D0),
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 15),
          if (loading)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(child: CircularProgressIndicator()),
            )
          else if (error != null)
            _PermitError(message: error!, onRetry: onRefresh)
          else if (permits.isEmpty)
            const _EmptyPermits(
              icon: Icons.route_outlined,
              title: 'هنوز سابقه سفری ثبت نشده است',
              subtitle:
                  'پس از پایان یا بسته‌شدن دوزوله، سابقه آن اینجا می‌ماند.',
            )
          else
            ...permits.map(
              (permit) => Padding(
                padding: const EdgeInsets.only(bottom: 11),
                child: _PermitCard(
                  permit: permit,
                  onOpenPermitCopy: () => onOpenPermitCopy(permit),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _DashboardCount extends StatelessWidget {
  const _DashboardCount({
    required this.icon,
    required this.value,
    required this.label,
  });

  final IconData icon;
  final int value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 9),
        decoration: BoxDecoration(
          color: const Color(0x24FFFFFF),
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: const Color(0x33FFFFFF)),
        ),
        child: Row(
          children: [
            Icon(icon, color: const Color(0xFFB9FFE7), size: 20),
            const SizedBox(width: 7),
            Text(
              '$value',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 17,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(width: 5),
            Expanded(
              child: Text(
                label,
                maxLines: 1,
                style: const TextStyle(color: Color(0xFFE2F3FF), fontSize: 8),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PermitCard extends StatelessWidget {
  const _PermitCard({
    required this.permit,
    this.active = false,
    this.tracking = false,
    this.anotherTripActive = false,
    this.trackingBusy = false,
    this.onStartTrip,
    this.onStopTrip,
    this.onOpenPermitCopy,
  });

  final DriverPermit permit;
  final bool active;
  final bool tracking;
  final bool anotherTripActive;
  final bool trackingBusy;
  final Future<void> Function()? onStartTrip;
  final Future<void> Function()? onStopTrip;
  final Future<void> Function()? onOpenPermitCopy;

  @override
  Widget build(BuildContext context) {
    final accent = active ? AppTheme.green : _statusColor(permit.statusKey);
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: () => _showPermitDetails(context, permit),
        borderRadius: BorderRadius.circular(18),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: accent.withValues(alpha: 0.35)),
            boxShadow: const [
              BoxShadow(
                color: Color(0x110F172A),
                blurRadius: 14,
                offset: Offset(0, 7),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      color: accent.withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      active
                          ? Icons.local_shipping_rounded
                          : Icons.route_rounded,
                      color: accent,
                    ),
                  ),
                  const SizedBox(width: 9),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'دوزوله ${permit.serialNumber}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        Text(
                          permit.dCode,
                          textDirection: TextDirection.ltr,
                          style: const TextStyle(
                            color: Color(0xFF94A3B8),
                            fontSize: 9,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: accent.withValues(alpha: 0.11),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      tracking
                          ? 'سفر فعال / در حال ردیابی'
                          : permit.statusLabel,
                      style: TextStyle(
                        color: accent,
                        fontSize: 8,
                        fontWeight: FontWeight.w900,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 9),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 7,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.trip_origin_rounded,
                      size: 16,
                      color: AppTheme.blue,
                    ),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        permit.origin,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 10),
                      ),
                    ),
                    const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 7),
                      child: Icon(
                        Icons.arrow_back_rounded,
                        size: 17,
                        color: Color(0xFF94A3B8),
                      ),
                    ),
                    Expanded(
                      child: Text(
                        permit.destination,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.left,
                        style: const TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 7),
              Row(
                children: [
                  const Icon(
                    Icons.public_rounded,
                    size: 16,
                    color: Color(0xFF64748B),
                  ),
                  const SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      permit.countryName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Color(0xFF475569),
                        fontSize: 10,
                      ),
                    ),
                  ),
                  Text(
                    'صدور ${permit.issueDate}',
                    style: const TextStyle(
                      color: Color(0xFF94A3B8),
                      fontSize: 8,
                    ),
                  ),
                  const SizedBox(width: 3),
                  const Icon(
                    Icons.chevron_left_rounded,
                    color: Color(0xFF94A3B8),
                    size: 20,
                  ),
                ],
              ),
              if (permit.printCopyEndpoint != null || active) ...[
                const SizedBox(height: 9),
                Row(
                  children: [
                    if (permit.printCopyEndpoint != null)
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: onOpenPermitCopy,
                          icon: const Icon(Icons.image_rounded, size: 17),
                          label: const Text('تصویر دوزوله'),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppTheme.blue,
                            side: const BorderSide(color: Color(0xFF9AC7F5)),
                            padding: const EdgeInsets.symmetric(vertical: 9),
                            textStyle: const TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ),
                      ),
                    if (permit.printCopyEndpoint != null && active)
                      const SizedBox(width: 8),
                    if (active)
                      Expanded(
                        child: FilledButton.icon(
                          onPressed:
                              trackingBusy ||
                                  permit.trackingItemId == null ||
                                  anotherTripActive
                              ? null
                              : tracking
                              ? onStopTrip
                              : onStartTrip,
                          icon: trackingBusy
                              ? const SizedBox(
                                  width: 15,
                                  height: 15,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Icon(
                                  tracking
                                      ? Icons.stop_circle_rounded
                                      : Icons.play_circle_fill_rounded,
                                  size: 17,
                                ),
                          label: Text(
                            permit.trackingItemId == null
                                ? 'در انتظار ردیابی'
                                : anotherTripActive
                                ? 'سفر دیگر فعال است'
                                : tracking
                                ? 'پایان سفر'
                                : 'شروع سفر',
                          ),
                          style: FilledButton.styleFrom(
                            backgroundColor: tracking
                                ? const Color(0xFFE11D48)
                                : AppTheme.green,
                            foregroundColor: Colors.white,
                            disabledBackgroundColor: const Color(0xFFE2E8F0),
                            disabledForegroundColor: const Color(0xFF64748B),
                            padding: const EdgeInsets.symmetric(vertical: 9),
                            textStyle: const TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
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

  static Color _statusColor(String status) => switch (status) {
    'approved' => const Color(0xFF0D9488),
    'pending' => const Color(0xFFF59E0B),
    'rejected' || 'lost' => const Color(0xFFE11D48),
    'expired' => const Color(0xFF7C3AED),
    _ => const Color(0xFF64748B),
  };
}

class _EmptyPermits extends StatelessWidget {
  const _EmptyPermits({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          Icon(icon, size: 48, color: const Color(0xFF94A3B8)),
          const SizedBox(height: 12),
          Text(title, style: const TextStyle(fontWeight: FontWeight.w900)),
          const SizedBox(height: 5),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: Color(0xFF64748B),
              fontSize: 10,
              height: 1.7,
            ),
          ),
        ],
      ),
    );
  }
}

class _PermitError extends StatelessWidget {
  const _PermitError({required this.message, required this.onRetry});

  final String message;
  final Future<void> Function({bool silent}) onRetry;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF1F2),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        children: [
          const Icon(Icons.cloud_off_rounded, color: Color(0xFFE11D48)),
          const SizedBox(height: 8),
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 10),
          FilledButton(onPressed: onRetry, child: const Text('تلاش مجدد')),
        ],
      ),
    );
  }
}

Future<void> _showPermitDetails(BuildContext context, DriverPermit permit) {
  final rows = <(String, String)>[
    ('وضعیت', permit.statusLabel),
    ('شماره دوزوله', permit.serialNumber),
    ('کد رهگیری', permit.dCode),
    ('کشور مقصد', permit.countryName),
    ('مبدا', permit.origin),
    ('مقصد', permit.destination),
    ('تاریخ صدور', permit.issueDate),
    ('اعتبار تا', permit.validUntil),
    ('شرکت', permit.companyName),
    ('نوع بار', permit.cargoType),
    ('نوع مجوز', permit.permitType),
    ('کد سفر', permit.tripCode),
    ('نوع ناوگان', permit.truckType),
    ('مبلغ', permit.totalAmount),
  ];
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => Directionality(
      textDirection: TextDirection.rtl,
      child: Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.sizeOf(context).height * 0.86,
        ),
        padding: const EdgeInsets.fromLTRB(18, 10, 18, 24),
        decoration: const BoxDecoration(
          color: Color(0xFFF8FAFC),
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            children: [
              Container(
                width: 42,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFCBD5E1),
                  borderRadius: BorderRadius.circular(5),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  const CircleAvatar(
                    backgroundColor: Color(0xFFE8FFF6),
                    foregroundColor: AppTheme.green,
                    child: Icon(Icons.description_rounded),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'جزئیات دوزوله ${permit.serialNumber}',
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
              if (permit.fleetPlate != 'ثبت نشده') ...[
                const SizedBox(height: 14),
                FittedBox(child: IranPlate(plate: permit.fleetPlate)),
              ],
              const SizedBox(height: 14),
              Expanded(
                child: ListView.separated(
                  itemCount: rows.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 8),
                  itemBuilder: (context, index) {
                    final row = rows[index];
                    return Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 13,
                        vertical: 12,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(14),
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
                              style: const TextStyle(
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

class _AssistantTab extends StatefulWidget {
  const _AssistantTab({
    required this.session,
    required this.activePermits,
    required this.unreadCount,
    required this.activeTrackingItemId,
    required this.loading,
    required this.error,
    required this.onRefresh,
    required this.onOpenPermits,
    required this.onOpenChats,
  });

  final DriverSession session;
  final List<DriverPermit> activePermits;
  final int unreadCount;
  final int? activeTrackingItemId;
  final bool loading;
  final String? error;
  final Future<void> Function() onRefresh;
  final VoidCallback onOpenPermits;
  final VoidCallback onOpenChats;

  @override
  State<_AssistantTab> createState() => _AssistantTabState();
}

class _AssistantTabState extends State<_AssistantTab> {
  final _questionController = TextEditingController();
  final _scrollController = ScrollController();
  final _speech = const AssistantSpeechService();
  final List<_AssistantMessage> _messages = [];
  bool _listening = false;

  DriverPermit? get _currentPermit {
    if (widget.activePermits.isEmpty) return null;
    if (widget.activeTrackingItemId == null) return widget.activePermits.first;
    return widget.activePermits
        .where((permit) => permit.trackingItemId == widget.activeTrackingItemId)
        .firstOrNull;
  }

  @override
  void initState() {
    super.initState();
    final firstName = widget.session.name.trim().split(RegExp(r'\s+')).first;
    _messages.add(
      _AssistantMessage(
        text:
            '$firstName عزیز سلام. درباره دوزوله، مسیر، اعتبار، پیام‌ها، ناوگان یا وضعیت ردیابی از من بپرسید.',
        fromDriver: false,
      ),
    );
  }

  @override
  void dispose() {
    _questionController.dispose();
    _scrollController.dispose();
    _speech.stopSpeaking();
    super.dispose();
  }

  String _normalize(String text) => text
      .trim()
      .toLowerCase()
      .replaceAll('ي', 'ی')
      .replaceAll('ك', 'ک')
      .replaceAll('ة', 'ه')
      .replaceAll(RegExp(r'[؟?!،,.]'), ' ')
      .replaceAll(RegExp(r'\s+'), ' ');

  bool _containsAny(String question, List<String> words) =>
      words.any(question.contains);

  String _answer(String rawQuestion) {
    final question = _normalize(rawQuestion);
    final permit = _currentPermit;
    final driver = widget.session.driver;
    String value(String key, [String fallback = 'ثبت نشده']) {
      final result = driver[key]?.toString().trim();
      return result == null || result.isEmpty ? fallback : result;
    }

    if (_containsAny(question, ['سلام', 'درود', 'صبح بخیر', 'شب بخیر'])) {
      return 'سلام ${widget.session.name}. من آماده‌ام؛ سؤال سفرتان را بپرسید.';
    }
    if (_containsAny(question, ['کمک', 'چه کار', 'چی بلدی', 'چه اطلاعات'])) {
      return 'می‌توانید درباره تعداد دوزوله‌ها، شماره دوزوله، مسیر، تاریخ اعتبار، بار، کد سفر، شرکت، پلاک ناوگان، پیام‌های جدید و وضعیت ردیابی سؤال کنید.';
    }
    if (_containsAny(question, [
      'گزارش',
      'خلاصه',
      'وضعیت من',
      'امروز چه خبر',
    ])) {
      final tripText = permit == null
          ? 'دوزوله فعالی ندارید'
          : 'دوزوله ${permit.serialNumber} برای مسیر ${permit.origin} به ${permit.destination} فعال است';
      final trackingText = widget.activeTrackingItemId == null
          ? 'ردیابی خاموش است'
          : 'ردیابی سفر فعال است';
      return '$tripText؛ ${widget.unreadCount} پیام خوانده‌نشده دارید و $trackingText.';
    }
    if (_containsAny(question, ['چند دوزوله', 'دوزوله فعال', 'تعداد دوزوله'])) {
      final count = widget.activePermits.length;
      return count == 0
          ? 'در حال حاضر دوزوله فعالی برای شما ثبت نشده است.'
          : 'شما $count دوزوله فعال دارید${permit == null ? '.' : '؛ شماره دوزوله بعدی ${permit.serialNumber} است.'}';
    }
    if (_containsAny(question, ['شماره دوزوله', 'سریال دوزوله', 'کد دوزوله'])) {
      return permit == null
          ? 'دوزوله فعالی برای اعلام شماره پیدا نکردم.'
          : 'شماره دوزوله فعال شما ${permit.serialNumber} و کد آن ${permit.dCode} است.';
    }
    if (_containsAny(question, ['مسیر', 'مبدا', 'مبدأ', 'مقصد', 'کجا برم'])) {
      return permit == null
          ? 'در حال حاضر مسیر فعالی برای شما ثبت نشده است.'
          : 'مسیر دوزوله ${permit.serialNumber} از ${permit.origin} به ${permit.destination} است و کشور مقصد ${permit.countryName} ثبت شده است.';
    }
    if (_containsAny(question, ['اعتبار', 'انقضا', 'تاریخ دوزوله', 'تا کی'])) {
      return permit == null
          ? 'دوزوله فعالی برای بررسی تاریخ اعتبار پیدا نکردم.'
          : 'دوزوله ${permit.serialNumber} در تاریخ ${permit.issueDate} صادر شده و تا ${permit.validUntil} اعتبار دارد.';
    }
    if (_containsAny(question, ['بار', 'محموله', 'نوع عملیات'])) {
      return permit == null
          ? 'اطلاعات بار فعالی برای شما پیدا نکردم.'
          : 'نوع بار یا عملیات این سفر «${permit.cargoType}» و نوع مجوز «${permit.permitType}» است.';
    }
    if (_containsAny(question, ['کد سفر', 'تریپ کد'])) {
      return permit == null
          ? 'کد سفر فعالی پیدا نکردم.'
          : 'کد سفر دوزوله ${permit.serialNumber} برابر ${permit.tripCode} است.';
    }
    if (_containsAny(question, ['شرکت', 'کارفرما'])) {
      return 'شرکت متصل به حساب شما «${widget.session.companyName}» است. برای ارتباط با شرکت وارد بخش پیام‌ها شوید.';
    }
    if (_containsAny(question, ['پلاک', 'ناوگان', 'ماشین', 'کامیون'])) {
      return 'ناوگان شما ${value('truck_type')} با پلاک ${value('truck_plate')} و کارت هوشمند ${value('truck_smart_card')} ثبت شده است.';
    }
    if (_containsAny(question, ['پیام', 'چت', 'گفتگو'])) {
      return widget.unreadCount == 0
          ? 'پیام خوانده‌نشده‌ای ندارید.'
          : 'شما ${widget.unreadCount} پیام خوانده‌نشده دارید. از دکمه پیام‌ها وارد گفتگو شوید.';
    }
    if (_containsAny(question, ['ردیابی', 'جی پی اس', 'gps', 'موقعیت'])) {
      return widget.activeTrackingItemId == null
          ? 'ردیابی سفر خاموش است. برای فعال‌سازی، دوزوله را باز کنید و دکمه شروع سفر و فعال‌سازی ردیابی را بزنید.'
          : 'ردیابی سفر فعال است و موقعیت شما به‌صورت زنده برای شرکت ارسال می‌شود.';
    }
    if (_containsAny(question, ['شروع سفر', 'سفر رو شروع'])) {
      return permit == null
          ? 'برای شما دوزوله آماده سفری پیدا نکردم.'
          : 'برای شروع سفر، دکمه دوزوله‌ها را بزنید، دوزوله ${permit.serialNumber} را باز کنید و «شروع سفر و فعال‌سازی ردیابی» را انتخاب کنید.';
    }
    if (_containsAny(question, ['تصویر', 'عکس دوزوله', 'برگه دوزوله'])) {
      return permit == null
          ? 'دوزوله فعالی برای نمایش تصویر پیدا نکردم.'
          : 'برای دیدن نسخه کشوری دوزوله ${permit.serialNumber}، وارد دوزوله‌ها شوید و «مشاهده تصویر دوزوله» را بزنید.';
    }
    if (_containsAny(question, ['شماره من', 'موبایل', 'تلفن من'])) {
      return 'شماره موبایل ثبت‌شده شما ${value('mobile')} است.';
    }
    return 'پاسخ این سؤال هنوز در اطلاعات دستیار تعریف نشده است. درباره مسیر، دوزوله، اعتبار، بار، شرکت، ناوگان، پیام‌ها یا ردیابی از من بپرسید.';
  }

  void _scrollToEnd() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scrollController.hasClients) return;
      _scrollController.animateTo(
        _scrollController.position.maxScrollExtent,
        duration: const Duration(milliseconds: 280),
        curve: Curves.easeOut,
      );
    });
  }

  Future<void> _sendQuestion(String rawText, {bool voice = false}) async {
    final text = rawText.trim();
    if (text.isEmpty) return;
    final answer = _answer(text);
    _questionController.clear();
    setState(() {
      _messages
        ..add(_AssistantMessage(text: text, fromDriver: true))
        ..add(_AssistantMessage(text: answer, fromDriver: false));
    });
    _scrollToEnd();
    if (voice) {
      try {
        await _speech.speak(answer);
      } on PlatformException {
        // پاسخ متنی حتی در صورت نبود موتور فارسی گفتار نمایش داده می‌شود.
      }
    }
  }

  Future<void> _listen() async {
    if (_listening) return;
    FocusScope.of(context).unfocus();
    setState(() => _listening = true);
    try {
      final text = await _speech.listen();
      if (!mounted) return;
      if (text.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('صدایی تشخیص داده نشد؛ دوباره تلاش کنید.'),
          ),
        );
      } else {
        await _sendQuestion(text, voice: true);
      }
    } on PlatformException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.message ?? 'گفت‌وگوی صوتی در دسترس نیست.'),
        ),
      );
    } finally {
      if (mounted) setState(() => _listening = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    const suggestions = [
      'مسیر سفرم کجاست؟',
      'دوزوله‌ام تا کی اعتبار دارد؟',
      'پیام جدید دارم؟',
      'ردیابی روشن است؟',
    ];
    return Padding(
      padding: const EdgeInsets.fromLTRB(13, 13, 13, 10),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 12),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppTheme.blue, Color(0xFF0D9488)],
              ),
              borderRadius: BorderRadius.circular(22),
            ),
            child: Row(
              children: [
                const _AiOrb(size: 64, selected: true),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'دستیار دوزوله آماده گفت‌وگوست',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 15,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      SizedBox(height: 4),
                      Text(
                        'سؤال را تایپ کنید یا با میکروفن بپرسید',
                        style: TextStyle(color: Color(0xFFDDF8F5), fontSize: 9),
                      ),
                    ],
                  ),
                ),
                Container(
                  width: 9,
                  height: 9,
                  decoration: const BoxDecoration(
                    color: Color(0xFF8BFFD9),
                    shape: BoxShape.circle,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 9),
          SizedBox(
            height: 35,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: suggestions.length,
              separatorBuilder: (_, _) => const SizedBox(width: 7),
              itemBuilder: (context, index) => ActionChip(
                label: Text(
                  suggestions[index],
                  style: const TextStyle(fontSize: 9),
                ),
                onPressed: () => _sendQuestion(suggestions[index]),
              ),
            ),
          ),
          const SizedBox(height: 9),
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: const Color(0xFFF5F8FC),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: const Color(0xFFE1EAF3)),
              ),
              child: ListView.builder(
                controller: _scrollController,
                padding: const EdgeInsets.all(12),
                itemCount: _messages.length,
                itemBuilder: (context, index) {
                  final message = _messages[index];
                  return _AssistantChatBubble(
                    message: message,
                    onSpeak: message.fromDriver
                        ? null
                        : () => _speech.speak(message.text),
                  );
                },
              ),
            ),
          ),
          const SizedBox(height: 9),
          Container(
            padding: const EdgeInsets.fromLTRB(7, 6, 8, 6),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFFDCE6F0)),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x0F0F2742),
                  blurRadius: 12,
                  offset: Offset(0, 5),
                ),
              ],
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                IconButton.filled(
                  onPressed: _listening ? null : _listen,
                  tooltip: 'پرسش صوتی',
                  style: IconButton.styleFrom(
                    backgroundColor: _listening
                        ? const Color(0xFFE11D48)
                        : AppTheme.green,
                  ),
                  icon: _listening
                      ? const SizedBox.square(
                          dimension: 19,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Icon(Icons.mic_rounded),
                ),
                const SizedBox(width: 6),
                Expanded(
                  child: TextField(
                    controller: _questionController,
                    minLines: 1,
                    maxLines: 3,
                    textInputAction: TextInputAction.send,
                    onSubmitted: _sendQuestion,
                    decoration: const InputDecoration(
                      hintText: 'سؤال خود را بنویسید…',
                      border: InputBorder.none,
                      filled: false,
                      contentPadding: EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 12,
                      ),
                    ),
                  ),
                ),
                IconButton(
                  onPressed: () => _sendQuestion(_questionController.text),
                  tooltip: 'ارسال',
                  icon: const Icon(Icons.send_rounded, color: AppTheme.blue),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _AssistantMessage {
  const _AssistantMessage({required this.text, required this.fromDriver});

  final String text;
  final bool fromDriver;
}

class _AssistantChatBubble extends StatelessWidget {
  const _AssistantChatBubble({required this.message, this.onSpeak});

  final _AssistantMessage message;
  final VoidCallback? onSpeak;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: message.fromDriver
          ? Alignment.centerRight
          : Alignment.centerLeft,
      child: Container(
        constraints: const BoxConstraints(maxWidth: 290),
        margin: const EdgeInsets.only(bottom: 9),
        padding: const EdgeInsets.fromLTRB(11, 9, 11, 9),
        decoration: BoxDecoration(
          color: message.fromDriver ? AppTheme.blue : Colors.white,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(message.fromDriver ? 16 : 4),
            bottomRight: Radius.circular(message.fromDriver ? 4 : 16),
          ),
          border: message.fromDriver
              ? null
              : Border.all(color: const Color(0xFFE1EAF3)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Flexible(
              child: Text(
                message.text,
                style: TextStyle(
                  color: message.fromDriver
                      ? Colors.white
                      : const Color(0xFF172235),
                  fontSize: 11,
                  height: 1.65,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            if (onSpeak != null) ...[
              const SizedBox(width: 5),
              InkWell(
                onTap: onSpeak,
                borderRadius: BorderRadius.circular(20),
                child: const Padding(
                  padding: EdgeInsets.all(3),
                  child: Icon(
                    Icons.volume_up_outlined,
                    size: 17,
                    color: AppTheme.green,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

// نگه‌داری موقت طرح گزارش‌محور برای امکان بازگشت بدون اثر روی خروجی release.
// ignore: unused_element
class _AssistantOverview extends StatelessWidget {
  const _AssistantOverview({
    required this.driverName,
    required this.activePermits,
    required this.unreadCount,
    required this.activeTrackingItemId,
    required this.loading,
    required this.error,
    required this.onRefresh,
    required this.onOpenPermits,
    required this.onOpenChats,
  });

  final String driverName;
  final List<DriverPermit> activePermits;
  final int unreadCount;
  final int? activeTrackingItemId;
  final bool loading;
  final String? error;
  final Future<void> Function() onRefresh;
  final VoidCallback onOpenPermits;
  final VoidCallback onOpenChats;

  DriverPermit? get _currentPermit {
    if (activePermits.isEmpty) return null;
    if (activeTrackingItemId == null) return activePermits.first;
    return activePermits
        .where((permit) => permit.trackingItemId == activeTrackingItemId)
        .firstOrNull;
  }

  String get _briefing {
    if (loading) return 'در حال دریافت تازه‌ترین اطلاعات شما از سامانه هستم.';
    if (error != null) {
      return 'برای دریافت گزارش تازه، اتصال اینترنت را بررسی و دوباره تلاش کنید.';
    }
    final permit = _currentPermit;
    final parts = <String>[];
    if (activeTrackingItemId != null) {
      parts.add('ردیابی سفر شما فعال است و موقعیت برای شرکت ارسال می‌شود');
    } else if (permit != null) {
      parts.add(
        'دوزوله ${permit.serialNumber} برای مسیر ${permit.origin} به ${permit.destination} آماده است',
      );
    } else {
      parts.add('در حال حاضر دوزوله فعالی برای شما ثبت نشده است');
    }
    parts.add(
      unreadCount > 0
          ? '$unreadCount پیام خوانده‌نشده دارید'
          : 'پیام خوانده‌نشده‌ای ندارید',
    );
    return '${parts.join('؛ ')}.';
  }

  @override
  Widget build(BuildContext context) {
    final permit = _currentPermit;
    final firstName =
        driverName.trim().split(RegExp(r'\s+')).firstOrNull ?? 'راننده';
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
        children: [
          Container(
            padding: const EdgeInsets.fromLTRB(18, 20, 18, 18),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
                colors: [AppTheme.blue, Color(0xFF0D9488)],
              ),
              borderRadius: BorderRadius.circular(26),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x251168D8),
                  blurRadius: 22,
                  offset: Offset(0, 10),
                ),
              ],
            ),
            child: Column(
              children: [
                const _AiOrb(size: 92, selected: true),
                const SizedBox(height: 15),
                Text(
                  '$firstName عزیز، من همراه شما هستم',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 19,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 10),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: .14),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: .25),
                    ),
                  ),
                  child: Text(
                    _briefing,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 11,
                      height: 1.8,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: _AssistantStat(
                  icon: Icons.description_outlined,
                  value: '${activePermits.length}',
                  label: 'دوزوله فعال',
                  color: AppTheme.blue,
                ),
              ),
              const SizedBox(width: 9),
              Expanded(
                child: _AssistantStat(
                  icon: Icons.mark_chat_unread_outlined,
                  value: '$unreadCount',
                  label: 'پیام جدید',
                  color: const Color(0xFFFF8A00),
                ),
              ),
              const SizedBox(width: 9),
              Expanded(
                child: _AssistantStat(
                  icon: Icons.gps_fixed_rounded,
                  value: activeTrackingItemId == null ? 'خاموش' : 'فعال',
                  label: 'ردیابی سفر',
                  color: AppTheme.green,
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          if (error != null)
            _AssistantNotice(
              icon: Icons.cloud_off_rounded,
              title: 'گزارش به‌روز نشد',
              subtitle: error!,
              color: const Color(0xFFE11D48),
            )
          else if (permit != null)
            _AssistantTripCard(
              permit: permit,
              tracking: activeTrackingItemId != null,
              onTap: onOpenPermits,
            )
          else if (!loading)
            const _AssistantNotice(
              icon: Icons.task_alt_rounded,
              title: 'کار فوری ندارید',
              subtitle:
                  'به‌محض صدور دوزوله یا دریافت پیام، دستیار شما را مطلع می‌کند.',
              color: AppTheme.green,
            ),
          const SizedBox(height: 18),
          const Text(
            'دسترسی سریع',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 9),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: onOpenPermits,
                  icon: const Icon(Icons.local_shipping_outlined),
                  label: const Text('دوزوله‌ها'),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: FilledButton.icon(
                  onPressed: onOpenChats,
                  icon: const Icon(Icons.forum_outlined),
                  label: Text(
                    unreadCount > 0 ? 'پیام‌ها ($unreadCount)' : 'پیام‌ها',
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 9),
          TextButton.icon(
            onPressed: loading ? null : onRefresh,
            icon: loading
                ? const SizedBox.square(
                    dimension: 17,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.refresh_rounded),
            label: const Text('به‌روزرسانی گزارش دستیار'),
          ),
        ],
      ),
    );
  }
}

class _AssistantStat extends StatelessWidget {
  const _AssistantStat({
    required this.icon,
    required this.value,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String value;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE1EAF3)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 5),
          Text(
            value,
            maxLines: 1,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            maxLines: 1,
            style: const TextStyle(color: Color(0xFF718096), fontSize: 8),
          ),
        ],
      ),
    );
  }
}

class _AssistantTripCard extends StatelessWidget {
  const _AssistantTripCard({
    required this.permit,
    required this.tracking,
    required this.onTap,
  });

  final DriverPermit permit;
  final bool tracking;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(21),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: tracking ? const Color(0xFFE9FFF6) : Colors.white,
            borderRadius: BorderRadius.circular(21),
            border: Border.all(
              color: tracking
                  ? const Color(0xFF9EE9D0)
                  : const Color(0xFFE1EAF3),
            ),
          ),
          child: Column(
            children: [
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: const Color(0xFFE8F4FF),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Icon(
                      tracking ? Icons.gps_fixed_rounded : Icons.route_rounded,
                      color: tracking ? AppTheme.green : AppTheme.blue,
                    ),
                  ),
                  const SizedBox(width: 11),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          tracking ? 'سفر در حال ردیابی' : 'سفر پیشنهادی بعدی',
                          style: const TextStyle(fontWeight: FontWeight.w900),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          'دوزوله ${permit.serialNumber}',
                          style: const TextStyle(
                            color: Color(0xFF718096),
                            fontSize: 10,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Icon(
                    Icons.chevron_left_rounded,
                    color: Color(0xFF94A3B8),
                  ),
                ],
              ),
              const SizedBox(height: 13),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 10,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .8),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        permit.origin,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ),
                    const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 8),
                      child: Icon(
                        Icons.arrow_back_rounded,
                        color: AppTheme.blue,
                      ),
                    ),
                    Expanded(
                      child: Text(
                        permit.destination,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.left,
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AssistantNotice extends StatelessWidget {
  const _AssistantNotice({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.color,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(19),
        border: Border.all(color: const Color(0xFFE1EAF3)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 28),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(fontWeight: FontWeight.w900),
                ),
                const SizedBox(height: 3),
                Text(
                  subtitle,
                  style: const TextStyle(
                    color: Color(0xFF718096),
                    fontSize: 9,
                    height: 1.6,
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

class _AiOrb extends StatefulWidget {
  const _AiOrb({required this.size, this.selected = false});
  final double size;
  final bool selected;

  @override
  State<_AiOrb> createState() => _AiOrbState();
}

class _AiOrbState extends State<_AiOrb> with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(seconds: 6),
  )..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return RepaintBoundary(
      child: AnimatedBuilder(
        animation: _controller,
        builder: (context, child) {
          final phase = _controller.value * math.pi * 2;
          final pulse = (math.sin(phase) + 1) / 2;
          final floatOffset = widget.size >= 70 ? math.sin(phase) * 2.2 : 0.0;

          return Transform.translate(
            offset: Offset(0, floatOffset),
            child: Transform.scale(
              scale: widget.selected ? 0.985 + (pulse * 0.025) : 1,
              child: Container(
                width: widget.size,
                height: widget.size,
                padding: EdgeInsets.all(widget.size * 0.1),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: const Color(0xFFF0FFFB),
                  border: Border.all(
                    color: widget.selected
                        ? const Color(0xFF2DE6B4)
                        : const Color(0xFF87F3D6),
                    width: 1.5,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Color(widget.selected ? 0x5532D6A0 : 0x3332D6A0),
                      blurRadius: (widget.selected ? 10 : 6) + (pulse * 8),
                      spreadRadius: widget.selected ? pulse * 2 : 0,
                    ),
                  ],
                ),
                child: CustomPaint(
                  painter: _OrbPainter(
                    progress: _controller.value,
                    pulse: pulse,
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _OrbPainter extends CustomPainter {
  const _OrbPainter({required this.progress, required this.pulse});
  final double progress;
  final double pulse;

  @override
  void paint(Canvas canvas, Size size) {
    final center = size.center(Offset.zero);
    final radius = size.shortestSide / 2;
    final sphere = Rect.fromCircle(center: center, radius: radius);
    canvas.drawCircle(
      center,
      radius,
      Paint()
        ..shader = const RadialGradient(
          center: Alignment(-0.35, -0.4),
          radius: 1.05,
          colors: [Color(0xFFFFFFFF), Color(0xFF76F5D9), Color(0xFF16BFAE)],
          stops: [0, 0.42, 1],
        ).createShader(sphere),
    );
    canvas.save();
    canvas.clipPath(Path()..addOval(sphere));
    final lines = Paint()
      ..color = const Color(0xD9FFFFFF)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;
    final shift = (progress * radius * 2) - radius;
    for (var index = -2; index <= 2; index++) {
      canvas.drawOval(
        Rect.fromCenter(
          center: Offset(center.dx + shift + (index * radius), center.dy),
          width: radius * 0.72,
          height: radius * 2,
        ),
        lines,
      );
    }
    for (final scale in [0.42, 0.76]) {
      canvas.drawOval(
        Rect.fromCenter(
          center: center,
          width: radius * 2,
          height: radius * 2 * scale,
        ),
        lines,
      );
    }
    canvas.restore();

    final orbit = Paint()
      ..color = Color.fromRGBO(255, 255, 255, 0.38 + (pulse * 0.35))
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round
      ..strokeWidth = math.max(1.0, radius * 0.045);
    canvas.drawArc(
      sphere.deflate(radius * 0.07),
      (progress * math.pi * 2) - (math.pi / 2),
      math.pi * 0.72,
      false,
      orbit,
    );

    final angle = (progress * math.pi * 2) - (math.pi / 2);
    final satellite = Offset(
      center.dx + (math.cos(angle) * radius * 0.9),
      center.dy + (math.sin(angle) * radius * 0.9),
    );
    canvas.drawCircle(
      satellite,
      math.max(1.4, radius * (0.055 + (pulse * 0.02))),
      Paint()..color = Colors.white,
    );

    canvas.drawCircle(
      Offset(center.dx - (radius * 0.28), center.dy - (radius * 0.3)),
      radius * (0.08 + (pulse * 0.025)),
      Paint()..color = const Color(0xB3FFFFFF),
    );
  }

  @override
  bool shouldRepaint(covariant _OrbPainter oldDelegate) =>
      oldDelegate.progress != progress || oldDelegate.pulse != pulse;
}
