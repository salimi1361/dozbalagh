import 'package:flutter/services.dart';

import '../config/app_config.dart';

class PermitPreviewService {
  const PermitPreviewService();

  static const _channel = MethodChannel(
    'ir.itcakh.dozoleh.mobile_driver/permit_preview',
  );

  Future<void> open({required String endpoint, required String token}) {
    final url = endpoint.startsWith('http')
        ? endpoint
        : '${AppConfig.apiOrigin}${endpoint.startsWith('/') ? '' : '/'}$endpoint';
    return _channel.invokeMethod<bool>('open', {'url': url, 'token': token});
  }
}
