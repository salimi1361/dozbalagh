class CmrFormat {
  const CmrFormat._();

  static String date(DateTime? value, {bool withTime = false}) {
    if (value == null) return 'ثبت نشده';
    final local = value.toLocal();
    final jalali = _fromGregorian(local.year, local.month, local.day);
    final text =
        '${jalali.$1.toString().padLeft(4, '0')}/'
        '${jalali.$2.toString().padLeft(2, '0')}/'
        '${jalali.$3.toString().padLeft(2, '0')}'
        '${withTime ? '  ${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}' : ''}';
    return digits(text);
  }

  static String digits(String value) {
    const english = '0123456789';
    const persian = '۰۱۲۳۴۵۶۷۸۹';
    return value.split('').map((char) {
      final index = english.indexOf(char);
      return index < 0 ? char : persian[index];
    }).join();
  }

  static (int, int, int) _fromGregorian(int year, int month, int day) {
    const monthDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    final gy = year - 1600;
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
    var jy = 979 + 33 * cycles + 4 * (jalaliDayNumber ~/ 1461);
    jalaliDayNumber %= 1461;
    if (jalaliDayNumber >= 366) {
      jy += (jalaliDayNumber - 1) ~/ 365;
      jalaliDayNumber = (jalaliDayNumber - 1) % 365;
    }
    final jm = jalaliDayNumber < 186
        ? 1 + jalaliDayNumber ~/ 31
        : 7 + (jalaliDayNumber - 186) ~/ 30;
    final jd =
        1 +
        (jalaliDayNumber < 186
            ? jalaliDayNumber % 31
            : (jalaliDayNumber - 186) % 30);
    return (jy, jm, jd);
  }
}
