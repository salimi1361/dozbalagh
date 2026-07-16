import 'package:flutter/material.dart';

class AppTheme {
  const AppTheme._();

  static const navy = Color(0xFF07111F);
  static const blue = Color(0xFF1168D8);
  static const cyan = Color(0xFF21A8FF);
  static const green = Color(0xFF12B886);
  static const surface = Color(0xFFF2F6FA);

  static ThemeData get light {
    final scheme = ColorScheme.fromSeed(seedColor: blue).copyWith(
      primary: blue,
      secondary: green,
      surface: Colors.white,
      error: const Color(0xFFE53935),
    );
    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      fontFamily: 'Vazirmatn',
      scaffoldBackgroundColor: surface,
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: const Color(0xFFF7F9FC),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 18,
          vertical: 17,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Color(0xFFDCE5EF)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Color(0xFFDCE5EF)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: blue, width: 1.6),
        ),
      ),
    );
  }
}
