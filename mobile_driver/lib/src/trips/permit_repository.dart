import 'dart:convert';
import 'dart:io';

import '../config/app_config.dart';

String? _optionalText(dynamic value) {
  final text = value?.toString().trim();
  return text == null || text.isEmpty ? null : text;
}

class DriverPermit {
  const DriverPermit({
    required this.id,
    required this.trackingItemId,
    required this.printCopyEndpoint,
    required this.serialNumber,
    required this.dCode,
    required this.statusKey,
    required this.statusLabel,
    required this.issueDate,
    required this.validUntil,
    required this.companyName,
    required this.countryName,
    required this.origin,
    required this.destination,
    required this.cargoType,
    required this.permitType,
    required this.tripCode,
    required this.fleetPlate,
    required this.truckType,
    required this.totalAmount,
    required this.itemCount,
  });

  final int id;
  final int? trackingItemId;
  final String? printCopyEndpoint;
  final String serialNumber;
  final String dCode;
  final String statusKey;
  final String statusLabel;
  final String issueDate;
  final String validUntil;
  final String companyName;
  final String countryName;
  final String origin;
  final String destination;
  final String cargoType;
  final String permitType;
  final String tripCode;
  final String fleetPlate;
  final String truckType;
  final String totalAmount;
  final int itemCount;

  static const activeStatuses = {
    'issued',
    'started_trip',
    'in_transit',
    'at_border_out',
    'at_destination',
  };

  bool get isActive => activeStatuses.contains(statusKey);

  factory DriverPermit.fromJson(Map<String, dynamic> json) {
    String text(String key, [String fallback = 'ثبت نشده']) {
      final value = json[key]?.toString().trim();
      return value == null || value.isEmpty ? fallback : value;
    }

    final items = json['dozoleh_items'];
    return DriverPermit(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      trackingItemId: int.tryParse(json['tracking_item_id']?.toString() ?? ''),
      printCopyEndpoint: _optionalText(json['print_copy_endpoint']),
      serialNumber: text('serial_number', 'بدون شماره'),
      dCode: text('d_code', '---'),
      statusKey: text('status_key', 'unknown'),
      statusLabel: text('status_label', 'وضعیت نامشخص'),
      issueDate: text('issue_date', '---'),
      validUntil: text('valid_until', '---'),
      companyName: text('company_name'),
      countryName: text('country_name'),
      origin: text('origin'),
      destination: text('destination'),
      cargoType: text('cargo_type'),
      permitType: text('permit_type'),
      tripCode: text('trip_code'),
      fleetPlate: text('fleet_plate'),
      truckType: text('truck_type'),
      totalAmount: text('total_amount'),
      itemCount: items is List ? items.length : 0,
    );
  }
}

class PermitRepository {
  Future<List<DriverPermit>> getPermits(String token) async {
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 15);
    try {
      final request = await client.getUrl(
        Uri.parse('${AppConfig.driverApi}/permits'),
      );
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
      final response = await request.close().timeout(
        const Duration(seconds: 25),
      );
      final body = await utf8.decoder.bind(response).join();
      final decoded = body.isEmpty ? <String, dynamic>{} : jsonDecode(body);
      final json = decoded is Map
          ? Map<String, dynamic>.from(decoded)
          : <String, dynamic>{};
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw PermitException(
          json['message']?.toString() ?? 'دریافت دوزوله‌ها انجام نشد.',
        );
      }
      final data = json['data'];
      if (data is! List) return const [];
      return data.whereType<Map>().expand((rawPermit) {
        final permit = Map<String, dynamic>.from(rawPermit);
        final rawItems = permit['dozoleh_items'];
        if (rawItems is! List) return [DriverPermit.fromJson(permit)];

        final issuedItems = rawItems
            .whereType<Map>()
            .map((item) => Map<String, dynamic>.from(item))
            .where(_isIssuedItem)
            .toList();
        if (issuedItems.isEmpty) return [DriverPermit.fromJson(permit)];

        return issuedItems.map((item) {
          final serial = item['serial_number']?.toString().trim() ?? '';
          final parentSerial = permit['serial_number']?.toString().trim() ?? '';
          final itemStatus = item['item_status']?.toString().trim();
          final effectiveStatus =
              itemStatus == null ||
                  itemStatus.isEmpty ||
                  itemStatus == 'pending'
              ? 'issued'
              : itemStatus;
          final merged = <String, dynamic>{
            ...permit,
            'tracking_item_id':
                item['tracking_item_id'] ??
                (serial == parentSerial ? permit['tracking_item_id'] : null),
            'print_copy_endpoint': item['print_copy_endpoint'],
            'serial_number': serial,
            'status_key': effectiveStatus,
            'status_label': _itemStatusLabel(effectiveStatus),
            'country_name': item['country_name'] ?? permit['country_name'],
            'origin': item['loading_origin'] ?? permit['origin'],
            'destination': item['loading_destination'] ?? permit['destination'],
            'permit_type': item['permit_type'] ?? permit['permit_type'],
            'cargo_type': item['operation_type'] ?? permit['cargo_type'],
            'trip_code': item['trip_code'] ?? permit['trip_code'],
            'issue_date': item['issue_date_jalali'] ?? permit['issue_date'],
            'valid_until': item['valid_until_jalali'] ?? permit['valid_until'],
            'dozoleh_items': [item],
          };
          return DriverPermit.fromJson(merged);
        });
      }).toList();
    } on PermitException {
      rethrow;
    } on SocketException {
      throw const PermitException('اتصال اینترنت برقرار نیست.');
    } catch (_) {
      throw const PermitException('دریافت اطلاعات سفرها انجام نشد.');
    } finally {
      client.close(force: true);
    }
  }

  static bool _isIssuedItem(Map<String, dynamic> item) {
    final serial = item['serial_number']?.toString().trim() ?? '';
    if (serial.isEmpty) return false;
    final status = item['item_status']?.toString().trim().toLowerCase();
    final returnStatus = item['return_status']?.toString().trim().toLowerCase();
    const closed = {
      'lost',
      'collected',
      'archived',
      'cancelled',
      'company_returned',
    };
    return !closed.contains(status) && !closed.contains(returnStatus);
  }

  static String _itemStatusLabel(String? status) => switch (status) {
    'started_trip' => 'سفر فعال / در حال ردیابی',
    'in_transit' => 'در حال ترانزیت',
    'at_border_out' => 'در مرز خروجی',
    'at_destination' => 'در مقصد',
    _ => 'صادر شده / آماده سفر',
  };
}

class PermitException implements Exception {
  const PermitException(this.message);
  final String message;
}
