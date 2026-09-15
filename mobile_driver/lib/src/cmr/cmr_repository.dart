import 'dart:convert';
import 'dart:io';
import 'dart:math';
import 'dart:typed_data';

import '../config/app_config.dart';

class DriverCmr {
  const DriverCmr({
    required this.id,
    required this.uuid,
    required this.number,
    required this.companySerial,
    required this.status,
    required this.companyName,
    required this.vehiclePlate,
    required this.takingOverPlace,
    required this.takingOverAt,
    required this.deliveryPlace,
    required this.plannedDeliveryAt,
    required this.issuedAt,
    required this.printUrl,
  });

  final int id;
  final String uuid;
  final String number;
  final String companySerial;
  final String status;
  final String companyName;
  final String vehiclePlate;
  final String takingOverPlace;
  final DateTime? takingOverAt;
  final String deliveryPlace;
  final DateTime? plannedDeliveryAt;
  final DateTime? issuedAt;
  final String printUrl;

  bool get isActive =>
      const ['issued', 'accepted', 'in_transit', 'delivered'].contains(status);

  factory DriverCmr.fromJson(Map<String, dynamic> json) => DriverCmr(
    id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
    uuid: _text(json['uuid']),
    number: _text(json['number'], 'بدون شماره'),
    companySerial: _text(json['company_serial']),
    status: _text(json['status'], 'unknown'),
    companyName: _text(json['company_name'], 'ثبت نشده'),
    vehiclePlate: _text(json['vehicle_plate']),
    takingOverPlace: _text(json['taking_over_place'], 'ثبت نشده'),
    takingOverAt: _date(json['taking_over_at']),
    deliveryPlace: _text(json['delivery_place'], 'ثبت نشده'),
    plannedDeliveryAt: _date(json['planned_delivery_at']),
    issuedAt: _date(json['issued_at']),
    printUrl: _text(json['print_url']),
  );
}

class CmrDetail {
  const CmrDetail({
    required this.summary,
    required this.version,
    required this.integrityHash,
    required this.consignor,
    required this.consignee,
    required this.carrier,
    required this.goods,
    required this.events,
    required this.signatures,
    required this.attachments,
    required this.handovers,
    required this.policy,
  });

  final DriverCmr summary;
  final int version;
  final String integrityHash;
  final CmrParty consignor;
  final CmrParty consignee;
  final CmrParty carrier;
  final List<CmrGood> goods;
  final List<CmrTimelineEvent> events;
  final List<CmrSignatureSummary> signatures;
  final List<CmrAttachmentSummary> attachments;
  final List<CmrHandoverSummary> handovers;
  final CmrHandoverPolicy policy;

  factory CmrDetail.fromJson(Map<String, dynamic> json) => CmrDetail(
    summary: DriverCmr.fromJson(json),
    version: int.tryParse(json['version']?.toString() ?? '') ?? 1,
    integrityHash: _text(json['integrity_hash']),
    consignor: CmrParty.fromJson(_map(json['consignor'])),
    consignee: CmrParty.fromJson(_map(json['consignee'])),
    carrier: CmrParty.fromJson(_map(json['carrier'])),
    goods: _list(json['goods']).map(CmrGood.fromJson).toList(),
    events: _list(json['events']).map(CmrTimelineEvent.fromJson).toList(),
    signatures: _list(
      json['signatures'],
    ).map(CmrSignatureSummary.fromJson).toList(),
    attachments: _list(
      json['attachments'],
    ).map(CmrAttachmentSummary.fromJson).toList(),
    handovers: _list(
      json['handovers'],
    ).map(CmrHandoverSummary.fromJson).toList(),
    policy: CmrHandoverPolicy.fromJson(_map(json['handover_policy'])),
  );
}

class CmrParty {
  const CmrParty({
    required this.name,
    required this.address,
    required this.countryCode,
  });
  final String name;
  final String address;
  final String countryCode;

  factory CmrParty.fromJson(Map<String, dynamic> json) => CmrParty(
    name: _text(json['name'], 'ثبت نشده'),
    address: _text(json['address']),
    countryCode: _text(json['country_code']),
  );
}

class CmrGood {
  const CmrGood({
    required this.lineNumber,
    required this.description,
    required this.marksAndNumbers,
    required this.packageType,
    required this.packageCount,
    required this.grossWeightKg,
    required this.volumeM3,
    required this.unNumber,
    required this.adrClass,
  });

  final int lineNumber;
  final String description;
  final String marksAndNumbers;
  final String packageType;
  final String packageCount;
  final String grossWeightKg;
  final String volumeM3;
  final String unNumber;
  final String adrClass;

  factory CmrGood.fromJson(Map<String, dynamic> json) => CmrGood(
    lineNumber: int.tryParse(json['line_number']?.toString() ?? '') ?? 0,
    description: _text(json['description'], 'کالای ثبت‌شده'),
    marksAndNumbers: _text(json['marks_and_numbers']),
    packageType: _text(json['package_type']),
    packageCount: _text(json['package_count']),
    grossWeightKg: _text(json['gross_weight_kg']),
    volumeM3: _text(json['volume_m3']),
    unNumber: _text(json['un_number']),
    adrClass: _text(json['adr_class']),
  );
}

class CmrTimelineEvent {
  const CmrTimelineEvent({
    required this.type,
    required this.description,
    required this.occurredAt,
  });
  final String type;
  final String description;
  final DateTime? occurredAt;

  factory CmrTimelineEvent.fromJson(Map<String, dynamic> json) =>
      CmrTimelineEvent(
        type: _text(json['type']),
        description: _text(json['description']),
        occurredAt: _date(json['occurred_at']),
      );
}

class CmrSignatureSummary {
  const CmrSignatureSummary({
    required this.role,
    required this.name,
    required this.signedAt,
    required this.version,
  });
  final String role;
  final String name;
  final DateTime? signedAt;
  final int version;

  factory CmrSignatureSummary.fromJson(Map<String, dynamic> json) =>
      CmrSignatureSummary(
        role: _text(json['role']),
        name: _text(json['name']),
        signedAt: _date(json['signed_at']),
        version: int.tryParse(json['version']?.toString() ?? '') ?? 1,
      );
}

class CmrAttachmentSummary {
  const CmrAttachmentSummary({
    required this.id,
    required this.type,
    required this.name,
    required this.sha256,
    required this.downloadUrl,
  });
  final int id;
  final String type;
  final String name;
  final String sha256;
  final String downloadUrl;

  factory CmrAttachmentSummary.fromJson(Map<String, dynamic> json) =>
      CmrAttachmentSummary(
        id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
        type: _text(json['type']),
        name: _text(json['name'], 'پیوست'),
        sha256: _text(json['sha256']),
        downloadUrl: _text(json['download_url']),
      );
}

class CmrHandoverSummary {
  const CmrHandoverSummary({
    required this.stage,
    required this.outcome,
    required this.signerName,
    required this.notes,
    required this.occurredAt,
  });
  final String stage;
  final String outcome;
  final String signerName;
  final String notes;
  final DateTime? occurredAt;

  factory CmrHandoverSummary.fromJson(Map<String, dynamic> json) =>
      CmrHandoverSummary(
        stage: _text(json['stage']),
        outcome: _text(json['outcome']),
        signerName: _text(json['signer_name']),
        notes: _text(json['notes']),
        occurredAt: _date(json['occurred_at']),
      );
}

class CmrStagePolicy {
  const CmrStagePolicy({
    required this.enabled,
    required this.requireSignature,
    required this.requirePhoto,
    required this.requireGps,
  });
  final bool enabled;
  final bool requireSignature;
  final bool requirePhoto;
  final bool requireGps;

  factory CmrStagePolicy.fromJson(Map<String, dynamic> json) => CmrStagePolicy(
    enabled: json['enabled'] == true,
    requireSignature: json['require_signature'] == true,
    requirePhoto: json['require_photo'] == true,
    requireGps: json['require_gps'] == true,
  );
}

class CmrHandoverPolicy {
  const CmrHandoverPolicy({
    required this.origin,
    required this.destination,
    required this.allowExceptions,
  });
  final CmrStagePolicy origin;
  final CmrStagePolicy destination;
  final bool allowExceptions;

  factory CmrHandoverPolicy.fromJson(Map<String, dynamic> json) =>
      CmrHandoverPolicy(
        origin: CmrStagePolicy.fromJson(_map(json['origin'])),
        destination: CmrStagePolicy.fromJson(_map(json['destination'])),
        allowExceptions: json['allow_exceptions'] == true,
      );
}

class CmrHandoverPayload {
  const CmrHandoverPayload({
    required this.clientUuid,
    required this.stage,
    required this.outcome,
    required this.documentVersion,
    required this.documentHash,
    required this.confirmed,
    this.signerName = '',
    this.signerIdentifier = '',
    this.notes = '',
    this.latitude,
    this.longitude,
    this.accuracy,
    this.signature,
    this.photos = const [],
  });

  final String clientUuid;
  final String stage;
  final String outcome;
  final int documentVersion;
  final String documentHash;
  final bool confirmed;
  final String signerName;
  final String signerIdentifier;
  final String notes;
  final double? latitude;
  final double? longitude;
  final double? accuracy;
  final Uint8List? signature;
  final List<String> photos;
}

class CmrRepository {
  Future<List<DriverCmr>> getDocuments(String token) async {
    final json = await _request(token, '');
    return _list(json['data']).map(DriverCmr.fromJson).toList();
  }

  Future<CmrDetail> getDocument(String token, int cmrId) async {
    final json = await _request(token, '/$cmrId');
    return CmrDetail.fromJson(_map(json['data']));
  }

  Future<void> updateStatus(
    String token,
    int cmrId,
    String action, {
    Map<String, dynamic>? data,
  }) async {
    await _request(token, '/$cmrId/$action', method: 'POST', data: data);
  }

  Future<void> submitHandover(
    String token,
    int cmrId,
    CmrHandoverPayload payload,
  ) async {
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 20);
    final boundary =
        '----dozoleh-${DateTime.now().microsecondsSinceEpoch}-${Random.secure().nextInt(1 << 32)}';
    try {
      final request = await client.postUrl(
        Uri.parse('${AppConfig.driverApi}/cmr/$cmrId/handover'),
      );
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
      request.headers.set(
        HttpHeaders.contentTypeHeader,
        'multipart/form-data; boundary=$boundary',
      );

      void field(String name, Object? value) {
        if (value == null || value.toString().isEmpty) return;
        request.add(
          utf8.encode(
            '--$boundary\r\nContent-Disposition: form-data; name="$name"\r\n\r\n$value\r\n',
          ),
        );
      }

      Future<void> file(
        String name,
        String filename,
        String contentType,
        List<int> bytes,
      ) async {
        request.add(
          utf8.encode(
            '--$boundary\r\nContent-Disposition: form-data; name="$name"; filename="$filename"\r\nContent-Type: $contentType\r\n\r\n',
          ),
        );
        request.add(bytes);
        request.add(utf8.encode('\r\n'));
      }

      field('client_uuid', payload.clientUuid);
      field('stage', payload.stage);
      field('outcome', payload.outcome);
      field('document_version', payload.documentVersion);
      field('document_hash', payload.documentHash);
      field('signer_name', payload.signerName.trim());
      field('signer_identifier', payload.signerIdentifier.trim());
      field('notes', payload.notes.trim());
      field('latitude', payload.latitude);
      field('longitude', payload.longitude);
      field('accuracy', payload.accuracy);
      field('occurred_at', DateTime.now().toUtc().toIso8601String());
      field('confirmed', payload.confirmed ? '1' : '0');

      if (payload.signature != null) {
        await file(
          'signature',
          'signature.png',
          'image/png',
          payload.signature!,
        );
      }
      for (var index = 0; index < payload.photos.length; index++) {
        final photo = File(payload.photos[index]);
        if (!await photo.exists()) continue;
        await file(
          'photos[$index]',
          'evidence_${index + 1}.jpg',
          'image/jpeg',
          await photo.readAsBytes(),
        );
      }
      request.add(utf8.encode('--$boundary--\r\n'));
      final response = await request.close().timeout(
        const Duration(seconds: 60),
      );
      await _decodeResponse(response);
    } on CmrException {
      rethrow;
    } on SocketException {
      throw const CmrException(
        'اتصال اینترنت برقرار نیست؛ اطلاعات فرم حفظ شده و می‌توانید دوباره ارسال کنید.',
      );
    } catch (_) {
      throw const CmrException('ثبت شواهد تحویل انجام نشد؛ دوباره تلاش کنید.');
    } finally {
      client.close(force: true);
    }
  }

  Future<Map<String, dynamic>> _request(
    String token,
    String path, {
    String method = 'GET',
    Map<String, dynamic>? data,
  }) async {
    final client = HttpClient()
      ..connectionTimeout = const Duration(seconds: 15);
    try {
      final uri = Uri.parse('${AppConfig.driverApi}/cmr$path');
      final request = method == 'POST'
          ? await client.postUrl(uri)
          : await client.getUrl(uri);
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
      if (method == 'POST') {
        request.headers.set(
          HttpHeaders.contentTypeHeader,
          'application/json; charset=utf-8',
        );
        request.write(jsonEncode(data ?? const <String, dynamic>{}));
      }
      final response = await request.close().timeout(
        const Duration(seconds: 30),
      );
      return await _decodeResponse(response);
    } on CmrException {
      rethrow;
    } on SocketException {
      throw const CmrException('اتصال اینترنت برقرار نیست.');
    } catch (_) {
      throw const CmrException('دریافت اطلاعات CMR انجام نشد.');
    } finally {
      client.close(force: true);
    }
  }

  Future<Map<String, dynamic>> _decodeResponse(
    HttpClientResponse response,
  ) async {
    final body = await utf8.decoder.bind(response).join();
    Map<String, dynamic> json = const {};
    if (body.isNotEmpty) {
      final decoded = jsonDecode(body);
      if (decoded is Map) json = Map<String, dynamic>.from(decoded);
    }
    if (response.statusCode < 200 || response.statusCode >= 300) {
      final errors = json['errors'];
      String? firstError;
      if (errors is Map) {
        for (final value in errors.values) {
          if (value is List && value.isNotEmpty) {
            firstError = value.first.toString();
            break;
          }
        }
      }
      throw CmrException(
        firstError ?? json['message']?.toString() ?? 'عملیات CMR انجام نشد.',
      );
    }
    return json;
  }
}

class CmrException implements Exception {
  const CmrException(this.message);
  final String message;
}

String newCmrClientUuid() {
  final random = Random.secure();
  final bytes = List<int>.generate(16, (_) => random.nextInt(256));
  bytes[6] = (bytes[6] & 0x0f) | 0x40;
  bytes[8] = (bytes[8] & 0x3f) | 0x80;
  final hex = bytes
      .map((value) => value.toRadixString(16).padLeft(2, '0'))
      .join();
  return '${hex.substring(0, 8)}-${hex.substring(8, 12)}-${hex.substring(12, 16)}-${hex.substring(16, 20)}-${hex.substring(20)}';
}

Map<String, dynamic> _map(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};
List<Map<String, dynamic>> _list(dynamic value) => value is List
    ? value
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList()
    : const [];
String _text(dynamic value, [String fallback = '']) {
  final text = value?.toString().trim();
  return text == null || text.isEmpty ? fallback : text;
}

DateTime? _date(dynamic value) =>
    DateTime.tryParse(value?.toString() ?? '')?.toLocal();
