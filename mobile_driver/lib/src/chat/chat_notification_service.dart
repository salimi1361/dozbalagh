import 'package:flutter/services.dart';

class ChatNotificationService {
  const ChatNotificationService();

  static const _channel = MethodChannel(
    'ir.itcakh.dozoleh.mobile_driver/notifications',
  );

  Future<void> requestPermission() async {
    try {
      await _channel.invokeMethod<void>('requestPermission');
    } on PlatformException {
      // ردکردن مجوز نباید badge داخل برنامه را متوقف کند.
    }
  }

  Future<void> showNewMessage({
    required String title,
    required String message,
    required int unreadCount,
  }) async {
    try {
      await _channel.invokeMethod<void>('showChatNotification', {
        'title': title,
        'message': message,
        'unread_count': unreadCount,
      });
    } on PlatformException {
      // اعلان سیستمی مکمل شمارنده داخل برنامه است.
    }
  }
}
