import 'package:flutter/services.dart';

import '../config/app_config.dart';

class TripTrackingService {
  const TripTrackingService();

  static const _channel = MethodChannel(
    'ir.itcakh.dozoleh.mobile_driver/trip_tracking',
  );

  Future<int?> getActiveItemId() async {
    final state = await _channel.invokeMapMethod<String, dynamic>('getState');
    if (state?['active'] != true) return null;
    return int.tryParse(state?['item_id']?.toString() ?? '');
  }

  Future<void> start({required int itemId, required String token}) async {
    await _channel.invokeMethod<bool>('start', {
      'item_id': itemId,
      'token': token,
      'api_base': AppConfig.driverApi,
    });
  }

  Future<void> startCmr({required int cmrId, required String token}) async {
    await _channel.invokeMethod<bool>('start', {
      'item_id': cmrId,
      'token': token,
      'api_base': '${AppConfig.driverApi}/cmr/$cmrId/tracking',
    });
  }

  Future<void> stop() => _channel.invokeMethod<bool>('stop');
}
