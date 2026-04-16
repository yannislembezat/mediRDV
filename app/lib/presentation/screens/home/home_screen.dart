import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/medecin_model.dart';
import '../../providers/appointment_provider.dart';
import '../../providers/auth_provider.dart';
import '../../providers/medecin_provider.dart';
import '../../widgets/app_bottom_navigation.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/appointment_card.dart';
import '../../widgets/doctor_card.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/skeleton_loader.dart';
import '../../widgets/specialty_chip.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(currentUserProvider);
    final nextAppointment = ref.watch(nextAppointmentProvider);
    final specialties = ref.watch(featuredSpecialtiesProvider);
    final medecins = ref.watch(medecinListProvider);
    final firstName = user?.fullName.split(' ').first ?? 'Patient';

    return AppScaffold(
      title: 'MediRDV',
      subtitle: 'Bonjour $firstName, votre espace patient Flutter est prêt.',
      bottomNavigationBar: const AppBottomNavigation(currentIndex: 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Prochain rendez-vous',
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          const SizedBox(height: AppSizes.md),
          nextAppointment.when(
            data: (appointment) {
              if (appointment == null) {
                return EmptyState(
                  title: 'Aucun rendez-vous confirmé',
                  message:
                      'Vous n\'avez pas encore de rendez-vous à venir.',
                  actionLabel: 'Chercher un médecin',
                  onActionPressed: () => context.go(AppRoutes.search),
                );
              }

              return AppointmentCard(
                appointment: appointment,
                onTap: () =>
                    context.push(AppRoutes.appointmentDetail(appointment.id)),
              );
            },
            error: (error, stackTrace) => const EmptyState(
              title: 'Impossible de charger vos données',
              message:
                  'Une erreur est survenue. Veuillez réessayer.',
            ),
            loading: () => const SkeletonLoader(lines: 4),
          ),
          AppSizes.sectionSpacing,
          Text(
            'Spécialités populaires',
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          const SizedBox(height: AppSizes.md),
          specialties.when(
            data: (items) => Wrap(
              spacing: AppSizes.sm,
              runSpacing: AppSizes.sm,
              children: items
                  .map((specialty) {
                    return SpecialtyChip(specialty: specialty);
                  })
                  .toList(growable: false),
            ),
            error: (error, stackTrace) => const SizedBox.shrink(),
            loading: () => const SkeletonLoader(lines: 2),
          ),
          AppSizes.sectionSpacing,
          Row(
            children: [
              Expanded(
                child: Text(
                  'Médecins disponibles',
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
              ),
              TextButton(
                onPressed: () => context.go(AppRoutes.search),
                child: const Text('Voir tout'),
              ),
            ],
          ),
          const SizedBox(height: AppSizes.md),
          medecins.when(
            data: (items) => Column(
              children: items
                  .take(2)
                  .map((medecin) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: AppSizes.md),
                      child: _DoctorPreviewCard(medecin: medecin),
                    );
                  })
                  .toList(growable: false),
            ),
            error: (error, stackTrace) => const EmptyState(
              title: 'Liste indisponible',
              message:
                  'Impossible de charger la liste des médecins. Veuillez réessayer.',
            ),
            loading: () => const SkeletonLoader(lines: 6),
          ),
        ],
      ),
    );
  }
}

class _DoctorPreviewCard extends StatelessWidget {
  const _DoctorPreviewCard({required this.medecin});

  final MedecinModel medecin;

  @override
  Widget build(BuildContext context) {
    return DoctorCard(
      medecin: medecin,
      onTap: () => context.push(AppRoutes.doctorDetail(medecin.id)),
      onBook: () => context.push(AppRoutes.bookDoctor(medecin.id)),
    );
  }
}
