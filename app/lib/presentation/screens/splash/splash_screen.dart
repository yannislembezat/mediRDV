import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_sizes.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/skeleton_loader.dart';

class SplashScreen extends ConsumerWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authControllerProvider);
    final message = switch (authState.status) {
      AuthStatus.checking => 'Vérification de votre session sécurisée',
      AuthStatus.authenticated => 'Ouverture de votre espace patient',
      AuthStatus.unauthenticated =>
        'Préparation de votre parcours de connexion',
    };

    return Scaffold(
      body: Container(
        width: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [AppColors.primaryDark, AppColors.primary],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: AppSizes.pagePadding,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                SvgPicture.asset('assets/icons/medirdv_logo.svg', height: 96),
                const SizedBox(height: AppSizes.lg),
                Text(
                  'MediRDV',
                  style: Theme.of(
                    context,
                  ).textTheme.displaySmall?.copyWith(color: AppColors.surface),
                ),
                const SizedBox(height: AppSizes.sm),
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: Theme.of(
                    context,
                  ).textTheme.bodyLarge?.copyWith(color: AppColors.surface),
                ),
                const SizedBox(height: AppSizes.xl),
                const SkeletonLoader(lines: 2),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
