import 'dart:async';
import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../device/device_registration_service.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

class PushNotificationService {
  PushNotificationService._();

  static final PushNotificationService instance = PushNotificationService._();

  static const AndroidNotificationChannel companyMessagesChannel =
      AndroidNotificationChannel(
        'company_messages',
        'پیام‌های شرکت',
        description: 'پیام‌های جدید شرکت برای راننده',
        importance: Importance.high,
        playSound: true,
        enableVibration: true,
      );
  static const AndroidNotificationChannel driverAlertsChannel =
      AndroidNotificationChannel(
        'driver_alerts',
        'اطلاعیه‌های رانندگان',
        description: 'پیام‌های فوری و اطلاعیه‌های مهم سامانه',
        importance: Importance.high,
        playSound: true,
        enableVibration: true,
      );

  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();
  final DeviceRegistrationService _deviceRegistration =
      DeviceRegistrationService();
  final StreamController<int> _companyNavigation =
      StreamController<int>.broadcast();
  final StreamController<void> _appUpdateRequests =
      StreamController<void>.broadcast();
  final StreamController<void> _announcementRequests =
      StreamController<void>.broadcast();

  Stream<int> get companyNavigation => _companyNavigation.stream;
  Stream<void> get appUpdateRequests => _appUpdateRequests.stream;
  Stream<void> get announcementRequests => _announcementRequests.stream;

  bool _initialized = false;
  String? _apiToken;
  int? _pendingCompanyId;
  bool _pendingAppUpdate = false;
  bool _pendingAnnouncement = false;

  Future<void> initialize() async {
    if (_initialized) return;
    await Firebase.initializeApp();
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

    const settings = InitializationSettings(
      android: AndroidInitializationSettings('ic_stat_chat'),
      iOS: DarwinInitializationSettings(),
    );
    await _localNotifications.initialize(
      settings: settings,
      onDidReceiveNotificationResponse: (response) {
        final payload = response.payload;
        if (payload == null || payload.isEmpty) return;
        try {
          final decoded = jsonDecode(payload);
          if (decoded is Map) {
            _handleNavigation(Map<String, dynamic>.from(decoded));
          }
        } on FormatException {
          // Payloadهای نامعتبر نباید اجرای برنامه را متوقف کنند.
        }
      },
    );
    await _localNotifications
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.createNotificationChannel(companyMessagesChannel);
    await _localNotifications
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.createNotificationChannel(driverAlertsChannel);

    FirebaseMessaging.onMessage.listen(_showForegroundMessage);
    FirebaseMessaging.onMessageOpenedApp.listen(
      (message) => _handleNavigation(message.data),
    );
    FirebaseMessaging.instance.onTokenRefresh.listen(_registerRefreshedToken);

    final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      _handleNavigation(initialMessage.data);
    }
    _initialized = true;
  }

  Future<void> activateSession(String apiToken) async {
    _apiToken = apiToken;
    final permission = await FirebaseMessaging.instance.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );
    final enabled =
        permission.authorizationStatus == AuthorizationStatus.authorized ||
        permission.authorizationStatus == AuthorizationStatus.provisional;
    final fcmToken = await FirebaseMessaging.instance.getToken();
    await _deviceRegistration.register(
      apiToken,
      fcmToken: fcmToken,
      notificationsEnabled: enabled,
    );
  }

  Future<void> deactivateSession() async {
    final apiToken = _apiToken;
    _apiToken = null;
    if (apiToken == null) return;
    try {
      final fcmToken = await FirebaseMessaging.instance.getToken();
      await _deviceRegistration.register(
        apiToken,
        fcmToken: fcmToken,
        notificationsEnabled: false,
      );
    } catch (_) {
      // خروج راننده نباید به در دسترس بودن شبکه وابسته باشد.
    }
  }

  int? takePendingCompanyId() {
    final value = _pendingCompanyId;
    _pendingCompanyId = null;
    return value;
  }

  bool takePendingAppUpdate() {
    final value = _pendingAppUpdate;
    _pendingAppUpdate = false;
    return value;
  }

  bool takePendingAnnouncement() {
    final value = _pendingAnnouncement;
    _pendingAnnouncement = false;
    return value;
  }

  Future<void> _registerRefreshedToken(String fcmToken) async {
    final apiToken = _apiToken;
    if (apiToken == null) return;
    try {
      await _deviceRegistration.register(
        apiToken,
        fcmToken: fcmToken,
        notificationsEnabled: true,
      );
    } catch (_) {
      // توکن در ورود یا refresh بعدی دوباره ثبت می‌شود.
    }
  }

  Future<void> _showForegroundMessage(RemoteMessage message) async {
    final data = message.data;
    if (!const {
      'company_message',
      'app_update',
      'driver_announcement',
    }.contains(data['type'])) {
      return;
    }
    final title =
        message.notification?.title ??
        data['title']?.toString() ??
        'پیام جدید شرکت';
    final body =
        message.notification?.body ??
        data['body']?.toString() ??
        'یک پیام جدید برای شما ارسال شده است.';
    final isAnnouncement = data['type']?.toString() == 'driver_announcement';
    await _localNotifications.show(
      id: message.messageId?.hashCode ?? DateTime.now().millisecondsSinceEpoch,
      title: title,
      body: body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          isAnnouncement ? 'driver_alerts' : 'company_messages',
          isAnnouncement ? 'اطلاعیه‌های رانندگان' : 'پیام‌های شرکت',
          channelDescription: isAnnouncement
              ? 'پیام‌های فوری و اطلاعیه‌های مهم سامانه'
              : 'پیام‌های جدید شرکت برای راننده',
          importance: Importance.high,
          priority: Priority.high,
          icon: 'ic_stat_chat',
        ),
        iOS: const DarwinNotificationDetails(),
      ),
      payload: jsonEncode(data),
    );
    if (const {
      'app_update',
      'driver_announcement',
    }.contains(data['type']?.toString())) {
      _handleNavigation(data);
    }
  }

  void _handleNavigation(Map<String, dynamic> data) {
    if (data['type']?.toString() == 'driver_announcement') {
      if (_announcementRequests.hasListener) {
        _announcementRequests.add(null);
      } else {
        _pendingAnnouncement = true;
      }
      return;
    }
    if (data['type']?.toString() == 'app_update') {
      if (_appUpdateRequests.hasListener) {
        _appUpdateRequests.add(null);
      } else {
        _pendingAppUpdate = true;
      }
      return;
    }
    if (data['type']?.toString() != 'company_message') return;
    var companyId = int.tryParse(data['company_id']?.toString() ?? '');
    if (companyId == null || companyId <= 0) {
      final conversationId = data['conversation_id']?.toString() ?? '';
      if (conversationId.startsWith('company:')) {
        companyId = int.tryParse(conversationId.substring('company:'.length));
      }
    }
    if (companyId == null || companyId <= 0) return;
    if (_companyNavigation.hasListener) {
      _companyNavigation.add(companyId);
    } else {
      _pendingCompanyId = companyId;
    }
  }
}
