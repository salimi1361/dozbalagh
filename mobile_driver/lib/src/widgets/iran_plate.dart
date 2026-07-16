import 'package:flutter/material.dart';

class IranPlate extends StatelessWidget {
  const IranPlate({required this.plate, super.key});

  final String plate;

  List<String> get _parts {
    final value = plate.trim();
    if (value.isEmpty || value == 'ثبت نشده') return const [];
    final dashed = value
        .split('-')
        .map((part) => part.trim())
        .where((part) => part.isNotEmpty)
        .toList();
    if (dashed.length >= 4) return dashed.take(4).toList();
    final tokens = RegExp(
      r'([0-9۰-۹]+)|([^0-9۰-۹\s]+)',
    ).allMatches(value).map((match) => match.group(0)!).toList();
    if (tokens.length >= 4) return tokens.take(4).toList();
    return [value];
  }

  @override
  Widget build(BuildContext context) {
    final parts = _parts;
    if (parts.length < 4) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 9),
        decoration: BoxDecoration(
          color: const Color(0xFFFFC62A),
          border: Border.all(color: const Color(0xFF111827), width: 1.5),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(
          parts.isEmpty ? 'بدون پلاک' : parts.first,
          textDirection: TextDirection.ltr,
          style: const TextStyle(fontWeight: FontWeight.w900),
        ),
      );
    }
    return Semantics(
      label: 'پلاک ناوگان $plate',
      child: Container(
        width: 260,
        height: 68,
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: const Color(0xFFFFC226),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFF111827), width: 2),
          boxShadow: const [
            BoxShadow(
              color: Color(0x220F172A),
              blurRadius: 12,
              offset: Offset(0, 6),
            ),
          ],
        ),
        child: Directionality(
          textDirection: TextDirection.ltr,
          child: Row(
            children: [
              Container(
                width: 39,
                padding: const EdgeInsets.fromLTRB(5, 7, 5, 5),
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Color(0xFF0756C9), Color(0xFF002B7F)],
                  ),
                  border: Border(
                    right: BorderSide(color: Color(0xFF111827), width: 2),
                  ),
                ),
                child: const Column(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _IranFlag(),
                    Text(
                      'I.R.\nIRAN',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 8,
                        height: 0.95,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(parts[0], style: _plateTextStyle),
                    const SizedBox(width: 9),
                    Text(parts[1], style: _plateTextStyle),
                    const SizedBox(width: 9),
                    Text(parts[2], style: _plateTextStyle),
                  ],
                ),
              ),
              Container(
                width: 55,
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Color(0xFFFFCD38), Color(0xFFF3B300)],
                  ),
                  border: Border(
                    left: BorderSide(color: Color(0xFF111827), width: 2),
                  ),
                ),
                child: Column(
                  children: [
                    const Expanded(
                      child: Center(
                        child: Text(
                          'ایران',
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ),
                    ),
                    Container(height: 1.5, color: const Color(0xFF111827)),
                    Expanded(
                      child: Center(
                        child: Text(parts[3], style: _plateTextStyle),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  static const _plateTextStyle = TextStyle(
    color: Color(0xFF020617),
    fontSize: 23,
    height: 1,
    fontWeight: FontWeight.w900,
  );
}

class _IranFlag extends StatelessWidget {
  const _IranFlag();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 25,
      height: 13,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(2),
        border: Border.all(color: const Color(0x66FFFFFF)),
      ),
      child: const Column(
        children: [
          Expanded(child: ColoredBox(color: Color(0xFF239F40))),
          Expanded(child: ColoredBox(color: Colors.white)),
          Expanded(child: ColoredBox(color: Color(0xFFDA0000))),
        ],
      ),
    );
  }
}
