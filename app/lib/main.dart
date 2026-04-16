import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'app.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('fr_FR');
  runApp(const ProviderScope(child: MediRdvApp()));
}

// Credentials:

// Admin: admin@medirdv.fr / admin123
// Patients: e.g. ahmed.benali@example.fr / patient123
// Doctors: e.g. fatima.zohra@medirdv.fr / doctor123
