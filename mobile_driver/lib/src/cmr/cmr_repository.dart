import 'dart:convert';
import 'dart:io';

import '../config/app_config.dart';

class DriverCmr {
  const DriverCmr({
    required this.id,
    required this.number,
    required this.companySerial,
    required this.status,
    required this.companyName,
    required this.takingOverPlace,
    required this.deliveryPlace,
    required this.issuedAt,
    required this.printUrl,
  });

  final int id;
  final String number;
  final String companySerial;
  final String status;
  final String companyName;
  final String takingOverPlace;
  final String deliveryPlace;
  final String issuedAt;
  final String printUrl;

  factory DriverCmr.fromJson(Map<String, dynamic> json) => DriverCmr(
        id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
        number: _text(json['number'], 'بدون شماره'),
        companySerial: _text(json['company_serial'], ''),
        status: _text(json['status'], 'unknown'),
        companyName: _text(json['company_name'], 'ثبت نشده'),
        takingOverPlace: _text(json['taking_over_place'], 'ثبت نشده'),
        deliveryPlace: _text(json['delivery_place'], 'ثبت نشده'),
        issuedAt: _text(json['issued_at'], '---'),
        printUrl: _text(json['print_url'], ''),
      );

  static String _text(dynamic value, String fallback) {
    final text = value?.toString().trim();
    return text == null || text.isEmpty ? fallback : text;
  }
}

class CmrRepository {
  Future<List<DriverCmr>> getDocuments(String token) async {
    final client = HttpClient()..connectionTimeout = const Duration(seconds: 15);
    try {
      final request = await client.getUrl(Uri.parse('${AppConfig.driverApi}/cmr'));
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
      final response = await request.close().timeout(const Duration(seconds: 25));
      final body = await utf8.decoder.bind(response).join();
      final decoded = body.isEmpty ? <String, dynamic>{} : jsonDecode(body);
      final json = decoded is Map ? Map<String, dynamic>.from(decoded) : <String, dynamic>{};
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw CmrException(json['message']?.toString() ?? 'دریافت CMRها انجام نشد.');
      }
      final data = json['data'];
      if (data is! List) return const [];
      return data
          .whereType<Map>()
          .map((item) => DriverCmr.fromJson(Map<String, dynamic>.from(item)))
          .toList();
    } on CmrException {
      rethrow;
    } on SocketException {
      throw const CmrException('اتصال اینترنت برقرار نیست.');
    } catch (_) {
      throw const CmrException('دریافت اسناد CMR انجام نشد.');
    } finally {
      client.close(force: true);
    }
  }

  Future<void> updateStatus(String token, int cmrId, String action, {Map<String, dynamic>? data}) async {
    final client = HttpClient()..connectionTimeout = const Duration(seconds: 15);
    try {
      final request = await client.postUrl(Uri.parse('${AppConfig.driverApi}/cmr/$cmrId/$action'));
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.contentTypeHeader, 'application/json; charset=utf-8');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
      request.write(jsonEncode(data ?? const <String, dynamic>{}));
      final response = await request.close().timeout(const Duration(seconds: 25));
      final body = await utf8.decoder.bind(response).join();
      final decoded = body.isEmpty ? <String, dynamic>{} : jsonDecode(body);
      final json = decoded is Map ? Map<String, dynamic>.from(decoded) : <String, dynamic>{};
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw CmrException(json['message']?.toString() ?? 'ثبت عملیات CMR انجام نشد.');
      }
    } on CmrException {
      rethrow;
    } on SocketException {
      throw const CmrException('اتصال اینترنت برقرار نیست.');
    } catch (_) {
      throw const CmrException('ثبت عملیات CMR انجام نشد.');
    } finally {
      client.close(force: true);
    }
  }
}

class CmrException implements Exception {
  const CmrException(this.message);
  final String message;
}
