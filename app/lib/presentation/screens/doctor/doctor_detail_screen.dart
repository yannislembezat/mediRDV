import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:table_calendar/table_calendar.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/router/app_router.dart';
import '../../../core/utils/date_formatter.dart';
import '../../providers/medecin_provider.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/doctor_card.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/skeleton_loader.dart';
import '../../widgets/time_slot_grid.dart';

class DoctorDetailScreen extends ConsumerStatefulWidget {
  const DoctorDetailScreen({super.key, required this.medecinId});

  final String medecinId;

  @override
  ConsumerState<DoctorDetailScreen> createState() => _DoctorDetailScreenState();
}

class _DoctorDetailScreenState extends ConsumerState<DoctorDetailScreen> {
  DateTime _selectedDay = DateTime.now();
  DateTime? _selectedSlot;

  @override
  Widget build(BuildContext context) {
    final medecin = ref.watch(medecinDetailProvider(widget.medecinId));
    final slots = ref.watch(
      medecinSlotsProvider((medecinId: widget.medecinId, day: _selectedDay)),
    );

    return AppScaffold(
      title: 'Fiche médecin',
      child: medecin.when(
        data: (item) {
          if (item == null) {
            return const EmptyState(
              title: 'Médecin introuvable',
              message:
                  'Ce médecin n\'est pas disponible.',
            );
          }

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              DoctorCard(
                medecin: item,
                onBook: () => context.push(AppRoutes.bookDoctor(item.id)),
              ),
              AppSizes.sectionSpacing,
              Text(
                'Disponibilités',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: AppSizes.md),
              Card(
                child: Padding(
                  padding: AppSizes.cardPadding,
                  child: TableCalendar<dynamic>(
                    locale: 'fr_FR',
                    firstDay: DateTime.now().subtract(const Duration(days: 30)),
                    lastDay: DateTime.now().add(const Duration(days: 90)),
                    focusedDay: _selectedDay,
                    selectedDayPredicate: (day) => isSameDay(day, _selectedDay),
                    headerStyle: const HeaderStyle(formatButtonVisible: false),
                    availableGestures: AvailableGestures.horizontalSwipe,
                    onDaySelected: (selectedDay, focusedDay) {
                      setState(() {
                        _selectedDay = selectedDay;
                        _selectedSlot = null;
                      });
                    },
                  ),
                ),
              ),
              const SizedBox(height: AppSizes.lg),
              Text(
                'Créneaux du ${DateFormatter.dayLabel(_selectedDay)}',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: AppSizes.md),
              slots.when(
                data: (items) {
                  if (items.isEmpty) {
                    return const EmptyState(
                      title: 'Aucun créneau disponible',
                      message:
                          'Ce médecin n\'a pas de créneaux disponibles pour cette date.',
                    );
                  }

                  return TimeSlotGrid(
                    slots: items,
                    selectedSlot: _selectedSlot,
                    onSelected: (slot) {
                      setState(() => _selectedSlot = slot);
                    },
                  );
                },
                error: (error, stackTrace) => const EmptyState(
                  title: 'Erreur de chargement',
                  message:
                      'Impossible de charger les créneaux. Veuillez réessayer.',
                ),
                loading: () => const SkeletonLoader(lines: 3),
              ),
            ],
          );
        },
        error: (error, stackTrace) => const EmptyState(
          title: 'Erreur de chargement',
          message:
              'Impossible de charger les informations du médecin. Veuillez réessayer.',
        ),
        loading: () => const SkeletonLoader(lines: 8),
      ),
    );
  }
}
