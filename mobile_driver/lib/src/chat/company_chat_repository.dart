import 'dart:convert';
import 'dart:io';

import '../config/app_config.dart';

class ChatConversation {
  const ChatConversation({
    required this.id,
    required this.type,
    required this.participantId,
    required this.title,
    required this.subtitle,
    required this.lastMessage,
    required this.lastMessageAt,
    required this.unreadCount,
    required this.canReply,
  });

  final String id;
  final String type;
  final int participantId;
  final String title;
  final String subtitle;
  final String lastMessage;
  final String lastMessageAt;
  final int unreadCount;
  final bool canReply;

  bool get isCompany => type == 'company';

  factory ChatConversation.fromJson(Map<String, dynamic> json) =>
      ChatConversation(
        id: json['id']?.toString() ?? '',
        type: json['type']?.toString() ?? 'company',
        participantId:
            int.tryParse(json['participant_id']?.toString() ?? '') ?? 0,
        title: json['title']?.toString() ?? 'گفتگو',
        subtitle: json['subtitle']?.toString() ?? '',
        lastMessage: json['last_message']?.toString() ?? '',
        lastMessageAt: _JalaliDate.format(json['last_message_at']?.toString()),
        unreadCount: int.tryParse(json['unread_count']?.toString() ?? '') ?? 0,
        canReply: json['can_reply'] != false,
      );
}

class CompanyChatMessage {
  const CompanyChatMessage({
    required this.id,
    required this.companyId,
    required this.sender,
    required this.message,
    required this.companyName,
    required this.createdAt,
    required this.read,
  });

  final int id;
  final int companyId;
  final String sender;
  final String message;
  final String companyName;
  final String createdAt;
  final bool read;

  bool get isDriver => sender == 'driver';

  factory CompanyChatMessage.fromJson(Map<String, dynamic> json) =>
      CompanyChatMessage(
        id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
        companyId: int.tryParse(json['company_id']?.toString() ?? '') ?? 0,
        sender: json['sender']?.toString() ?? 'company',
        message: json['message']?.toString() ?? '',
        companyName: json['company_name']?.toString() ?? 'شرکت حمل‌ونقل',
        createdAt: _JalaliDate.format(json['created_at']?.toString()),
        read: json['read_at'] != null,
      );
}

class CompanyChatRepository {
  bool _legacyConversationApi = false;

  Future<List<ChatConversation>> getConversations(String token) async {
    if (_legacyConversationApi) return _legacyConversations(token);
    try {
      final json = await _request(
        'GET',
        '/company-messages/conversations',
        token,
      );
      final data = json['data'];
      if (data is! List) return const [];
      return data
          .whereType<Map>()
          .map(
            (item) =>
                ChatConversation.fromJson(Map<String, dynamic>.from(item)),
          )
          .toList();
    } on CompanyChatException {
      _legacyConversationApi = true;
      return _legacyConversations(token);
    }
  }

  Future<List<ChatConversation>> _legacyConversations(String token) async {
    final json = await _request('GET', '/company-messages', token);
    final data = json['data'];
    if (data is! List) return const [];
    final grouped = <int, List<Map<String, dynamic>>>{};
    for (final raw in data.whereType<Map>()) {
      final item = Map<String, dynamic>.from(raw);
      final companyId = int.tryParse(item['company_id']?.toString() ?? '') ?? 0;
      if (companyId == 0) continue;
      grouped.putIfAbsent(companyId, () => []).add(item);
    }
    return grouped.entries.map((entry) {
      final latest = entry.value.first;
      final unread = entry.value.where((item) {
        return item['sender']?.toString() == 'company' &&
            item['read_at'] == null;
      }).length;
      return ChatConversation(
        id: 'company:${entry.key}',
        type: 'company',
        participantId: entry.key,
        title: latest['company_name']?.toString() ?? 'شرکت حمل‌ونقل',
        subtitle: 'گفتگوی راننده و شرکت',
        lastMessage: latest['message']?.toString() ?? '',
        lastMessageAt: _JalaliDate.format(latest['created_at']?.toString()),
        unreadCount: unread,
        canReply: true,
      );
    }).toList();
  }

  Future<List<CompanyChatMessage>> getMessages(
    String token, {
    int? companyId,
  }) async {
    final suffix = companyId == null ? '' : '?company_id=$companyId';
    final json = await _request('GET', '/company-messages$suffix', token);
    final data = json['data'];
    if (data is! List) return const [];
    final messages = data
        .whereType<Map>()
        .map(
          (item) =>
              CompanyChatMessage.fromJson(Map<String, dynamic>.from(item)),
        )
        .toList()
        .reversed
        .toList();
    if (companyId == null) return messages;
    return messages.where((message) => message.companyId == companyId).toList();
  }

  Future<void> markAsRead(String token, int id) async {
    await _request('POST', '/company-messages/$id/read', token);
  }

  Future<void> sendReply(String token, int messageId, String message) async {
    await _request(
      'POST',
      '/company-messages/reply',
      token,
      body: {'message_id': messageId, 'message': message},
    );
  }

  Future<Map<String, dynamic>> _request(
    String method,
    String path,
    String token, {
    Map<String, dynamic>? body,
  }) async {
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 15);
    try {
      final uri = Uri.parse('${AppConfig.driverApi}$path');
      final request = method == 'GET'
          ? await client.getUrl(uri)
          : await client.postUrl(uri);
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
      if (body != null) {
        request.headers.contentType = ContentType.json;
        request.write(jsonEncode(body));
      }
      final response = await request.close().timeout(
        const Duration(seconds: 25),
      );
      final text = await utf8.decoder.bind(response).join();
      final decoded = text.isEmpty ? <String, dynamic>{} : jsonDecode(text);
      final json = decoded is Map
          ? Map<String, dynamic>.from(decoded)
          : <String, dynamic>{};
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw CompanyChatException(
          json['message']?.toString() ??
              'ارتباط با بخش پیام‌های شرکت انجام نشد.',
        );
      }
      return json;
    } on CompanyChatException {
      rethrow;
    } on SocketException {
      throw const CompanyChatException('اتصال اینترنت برقرار نیست.');
    } catch (_) {
      throw const CompanyChatException('دریافت پیام‌های شرکت انجام نشد.');
    } finally {
      client.close(force: true);
    }
  }
}

class CompanyChatException implements Exception {
  const CompanyChatException(this.message);
  final String message;
}

class _JalaliDate {
  const _JalaliDate._();

  static String format(String? raw) {
    if (raw == null || raw.trim().isEmpty) return '';
    final normalized = raw.trim().replaceAll('/', '-').replaceFirst(' ', 'T');
    final date = DateTime.tryParse(normalized);
    if (date == null) return raw;
    final jalali = _fromGregorian(date.year, date.month, date.day);
    final value =
        '${jalali.$1.toString().padLeft(4, '0')}/'
        '${jalali.$2.toString().padLeft(2, '0')}/'
        '${jalali.$3.toString().padLeft(2, '0')} '
        '${date.hour.toString().padLeft(2, '0')}:'
        '${date.minute.toString().padLeft(2, '0')}';
    return _persianDigits(value);
  }

  static (int, int, int) _fromGregorian(int year, int month, int day) {
    const monthDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var gy = year - 1600;
    final gm = month - 1;
    final gd = day - 1;
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

  static String _persianDigits(String value) {
    const english = '0123456789';
    const persian = '۰۱۲۳۴۵۶۷۸۹';
    return value.split('').map((char) {
      final index = english.indexOf(char);
      return index < 0 ? char : persian[index];
    }).join();
  }
}
