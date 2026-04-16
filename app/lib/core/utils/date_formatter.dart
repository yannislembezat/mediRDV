import 'package:intl/intl.dart';

abstract final class DateFormatter {
  static final DateFormat _dayLabel = DateFormat('EEE d MMM', 'fr_FR');
  static final DateFormat _fullDateTime = DateFormat(
    'EEEE d MMMM y à HH:mm',
    'fr_FR',
  );
  static final DateFormat _timeOnly = DateFormat('HH:mm', 'fr_FR');

  static String dayLabel(DateTime value) =>
      _capitalize(_dayLabel.format(value));

  static String fullDateTime(DateTime value) =>
      _capitalize(_fullDateTime.format(value));

  static String time(DateTime value) => _timeOnly.format(value);

  static String _capitalize(String value) {
    if (value.isEmpty) {
      return value;
    }

    return value[0].toUpperCase() + value.substring(1);
  }
}
