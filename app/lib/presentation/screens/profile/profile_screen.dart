import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_sizes.dart';
import '../../../core/router/app_router.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/app_bottom_navigation.dart';
import '../../widgets/app_scaffold.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);

    return AppScaffold(
      title: 'Profil',
      bottomNavigationBar: const AppBottomNavigation(currentIndex: 4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Card(
            child: Padding(
              padding: AppSizes.cardPadding,
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: AppColors.infoSoft,
                    foregroundColor: AppColors.primary,
                    child: const Icon(BootstrapIcons.person_fill),
                  ),
                  const SizedBox(width: AppSizes.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user?.fullName ?? 'Patient MediRDV',
                          style: Theme.of(context).textTheme.headlineSmall,
                        ),
                        const SizedBox(height: AppSizes.xs),
                        Text(user?.email ?? 'patient@medirdv.fr'),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          AppSizes.sectionSpacing,
          ElevatedButton.icon(
            onPressed: () => context.push(AppRoutes.editProfile),
            icon: const Icon(BootstrapIcons.pencil_square),
            label: const Text('Modifier mon profil'),
          ),
          const SizedBox(height: AppSizes.sm),
          OutlinedButton.icon(
            onPressed: () async {
              await ref.read(authControllerProvider.notifier).signOut();
            },
            icon: const Icon(BootstrapIcons.box_arrow_right),
            label: const Text('Se déconnecter'),
          ),
        ],
      ),
    );
  }
}
