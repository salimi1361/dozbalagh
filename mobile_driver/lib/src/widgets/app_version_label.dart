import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class AppVersionLabel extends StatefulWidget {
  const AppVersionLabel({
    this.color = const Color(0xFF64748B),
    this.fontSize = 9,
    super.key,
  });

  final Color color;
  final double fontSize;

  @override
  State<AppVersionLabel> createState() => _AppVersionLabelState();
}

class _AppVersionLabelState extends State<AppVersionLabel> {
  static const _channel = MethodChannel(
    'ir.itcakh.dozoleh.mobile_driver/device_info',
  );
  static Future<String>? _cachedLabel;

  late final Future<String> _label = _cachedLabel ??= _loadLabel();

  static Future<String> _loadLabel() async {
    try {
      final info = await _channel.invokeMapMethod<String, dynamic>(
        'getDeviceInfo',
      );
      final version = info?['app_version']?.toString().trim();
      final build = info?['app_build']?.toString().trim();
      if (version == null || version.isEmpty) return 'نسخه برنامه';
      if (build == null || build.isEmpty) return 'نسخه $version';
      return 'نسخه $version  •  ساخت $build';
    } on PlatformException {
      return 'نسخه برنامه';
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<String>(
      future: _label,
      builder: (context, snapshot) => Text(
        snapshot.data ?? 'نسخه برنامه',
        textDirection: TextDirection.rtl,
        style: TextStyle(
          color: widget.color,
          fontSize: widget.fontSize,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
