import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/router/app_router.dart';
import '../../providers/medical_record_provider.dart';
import '../../widgets/app_bottom_navigation.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/medical_record_tile.dart';
import '../../widgets/skeleton_loader.dart';

class MedicalRecordsScreen extends ConsumerWidget {
  const MedicalRecordsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final records = ref.watch(medicalRecordListProvider);

    return AppScaffold(
      title: 'Dossier médical',
      subtitle:
          'Historique des consultations finalisées et des ordonnances associées.',
      bottomNavigationBar: const AppBottomNavigation(currentIndex: 3),
      child: records.when(
        data: (items) {
          if (items.isEmpty) {
            return const EmptyState(
              title: 'Aucun dossier disponible',
              message:
                  'Les consultations apparaîtront ici une fois complétées côté médecin.',
            );
          }

          return Column(
            children: items
                .map((record) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: AppSizes.md),
                    child: MedicalRecordTile(
                      consultation: record,
                      onTap: () =>
                          context.push(AppRoutes.consultationDetail(record.id)),
                    ),
                  );
                })
                .toList(growable: false),
          );
        },
        error: (error, stackTrace) => const EmptyState(
          title: 'Impossible de charger le dossier',
          message:
              'Une erreur est survenue. Veuillez réessayer.',
        ),
        loading: () => const SkeletonLoader(lines: 5),
      ),
    );
  }
}
