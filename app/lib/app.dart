import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/constants/api_endpoints.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';

class MediRdvApp extends ConsumerWidget {
  const MediRdvApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);

    return MaterialApp.router(
      title: ApiEndpoints.appName,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      routerConfig: router,
    );
  }
}
