import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/appointment_model.dart';
import '../../providers/appointment_provider.dart';
import '../../widgets/app_bottom_navigation.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/appointment_card.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/skeleton_loader.dart';

class AppointmentsListScreen extends ConsumerStatefulWidget {
  const AppointmentsListScreen({super.key});

  @override
  ConsumerState<AppointmentsListScreen> createState() =>
      _AppointmentsListScreenState();
}

class _AppointmentsListScreenState
    extends ConsumerState<AppointmentsListScreen> {
  AppointmentStatus? _selectedStatus;

  @override
  Widget build(BuildContext context) {
    final appointments = ref.watch(appointmentListProvider);

    return AppScaffold(
      title: 'Mes rendez-vous',
      bottomNavigationBar: const AppBottomNavigation(currentIndex: 2),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Wrap(
            spacing: AppSizes.sm,
            runSpacing: AppSizes.sm,
            children: [
              ChoiceChip(
                label: const Text('Tous'),
                selected: _selectedStatus == null,
                onSelected: (selected) =>
                    setState(() => _selectedStatus = null),
              ),
              ...AppointmentStatus.values.map((status) {
                return ChoiceChip(
                  label: Text(_labelForStatus(status)),
                  selected: _selectedStatus == status,
                  onSelected: (_) {
                    setState(() => _selectedStatus = status);
                  },
                );
              }),
            ],
          ),
          AppSizes.sectionSpacing,
          appointments.when(
            data: (items) {
              final filtered = _selectedStatus == null
                  ? items
                  : items
                        .where((item) => item.status == _selectedStatus)
                        .toList();

              if (filtered.isEmpty) {
                return const EmptyState(
                  title: 'Aucun rendez-vous pour ce filtre',
                  message:
                      'Aucun rendez-vous ne correspond à votre sélection.',
                );
              }

              return Column(
                children: filtered
                    .map((appointment) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: AppSizes.md),
                        child: AppointmentCard(
                          appointment: appointment,
                          onTap: () => context.push(
                            AppRoutes.appointmentDetail(appointment.id),
                          ),
                        ),
                      );
                    })
                    .toList(growable: false),
              );
            },
            error: (error, stackTrace) => const EmptyState(
              title: 'Impossible de charger les rendez-vous',
              message:
                  'Une erreur est survenue. Veuillez réessayer.',
            ),
            loading: () => const SkeletonLoader(lines: 6),
          ),
        ],
      ),
    );
  }

  String _labelForStatus(AppointmentStatus status) {
    return switch (status) {
      AppointmentStatus.pending => 'En attente',
      AppointmentStatus.confirmed => 'Confirmé',
      AppointmentStatus.refused => 'Refusé',
      AppointmentStatus.completed => 'Complété',
      AppointmentStatus.cancelled => 'Annulé',
    };
  }
}
