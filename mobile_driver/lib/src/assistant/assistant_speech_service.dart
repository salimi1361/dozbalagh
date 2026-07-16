import 'package:flutter/services.dart';

class AssistantSpeechService {
  const AssistantSpeechService();

  static const _channel = MethodChannel(
    'ir.itcakh.dozoleh.mobile_driver/assistant_speech',
  );

  Future<String> listen() async {
    final text = await _channel.invokeMethod<String>('listen');
    return text?.trim() ?? '';
  }

  Future<void> speak(String text) async {
    final value = text.trim();
    if (value.isEmpty) return;
    await _channel.invokeMethod<void>('speak', {'text': value});
  }

  Future<void> stopSpeaking() => _channel.invokeMethod<void>('stopSpeaking');
}
