import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/router/app_router.dart';
import '../../../core/utils/date_formatter.dart';
import '../../providers/appointment_provider.dart';
import '../../widgets/app_bottom_navigation.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/status_badge.dart';

class AppointmentDetailScreen extends ConsumerWidget {
  const AppointmentDetailScreen({super.key, required this.appointmentId});

  final String appointmentId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final appointment = ref.watch(appointmentDetailProvider(appointmentId));

    return AppScaffold(
      title: 'Détail du rendez-vous',
      bottomNavigationBar: const AppBottomNavigation(currentIndex: 2),
      child: appointment.when(
        data: (item) {
          if (item == null) {
            return const EmptyState(
              title: 'Rendez-vous introuvable',
              message:
                  'Ce rendez-vous n\'est pas disponible.',
            );
          }

          return Card(
            child: Padding(
              padding: AppSizes.cardPadding,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          item.medecin.fullName,
                          style: Theme.of(context).textTheme.displaySmall,
                        ),
                      ),
                      StatusBadge(status: item.status),
                    ],
                  ),
                  const SizedBox(height: AppSizes.md),
                  Text(item.medecin.specialty.name),
                  const SizedBox(height: AppSizes.lg),
                  _Line(
                    label: 'Date',
                    value: DateFormatter.fullDateTime(item.scheduledAt),
                  ),
                  _Line(label: 'Lieu', value: item.location),
                  _Line(label: 'Motif', value: item.reason),
                  if (item.adminNote != null) ...[
                    const SizedBox(height: AppSizes.md),
                    Text(
                      'Note administrative',
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: AppSizes.xs),
                    Text(item.adminNote!),
                  ],
                  if (item.hasMedicalRecord && item.consultationId != null) ...[
                    const SizedBox(height: AppSizes.xl),
                    ElevatedButton(
                      onPressed: () => context.push(
                        AppRoutes.consultationDetail(item.consultationId!),
                      ),
                      child: const Text('Voir la consultation liée'),
                    ),
                  ],
                ],
              ),
            ),
          );
        },
        error: (error, stackTrace) => const EmptyState(
          title: 'Impossible de charger le rendez-vous',
          message:
              'Une erreur est survenue. Veuillez réessayer.',
        ),
        loading: () => const CircularProgressIndicator(),
      ),
    );
  }
}

class _Line extends StatelessWidget {
  const _Line({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSizes.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: AppSizes.xs),
          Text(value, style: Theme.of(context).textTheme.bodyLarge),
        ],
      ),
    );
  }
}
