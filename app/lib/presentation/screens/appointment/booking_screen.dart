import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:table_calendar/table_calendar.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/router/app_router.dart';
import '../../providers/appointment_provider.dart';
import '../../providers/medecin_provider.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/skeleton_loader.dart';
import '../../widgets/time_slot_grid.dart';

class BookingScreen extends ConsumerStatefulWidget {
  const BookingScreen({super.key, required this.medecinId});

  final String medecinId;

  @override
  ConsumerState<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends ConsumerState<BookingScreen> {
  DateTime _selectedDay = DateTime.now();
  DateTime? _selectedSlot;
  final TextEditingController _reasonController = TextEditingController();

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_selectedSlot == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Sélectionnez un créneau avant de continuer.'),
        ),
      );
      return;
    }

    final ok = await ref.read(bookingControllerProvider.notifier).book(
      medecinId: widget.medecinId,
      dateTime: _selectedSlot!,
      reason: _reasonController.text.trim().isEmpty
          ? null
          : _reasonController.text.trim(),
    );

    if (!mounted) return;

    if (ok) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Demande de rendez-vous envoyée.')),
      );
      context.go(AppRoutes.appointments);
    } else {
      final error =
          ref.read(bookingControllerProvider).errorMessage ??
          'Une erreur est survenue.';
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(error)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final medecin = ref.watch(medecinDetailProvider(widget.medecinId));
    final slots = ref.watch(
      medecinSlotsProvider((medecinId: widget.medecinId, day: _selectedDay)),
    );
    final booking = ref.watch(bookingControllerProvider);

    return AppScaffold(
      title: 'Demande de rendez-vous',
      child: medecin.when(
        data: (item) {
          if (item == null) {
            return const EmptyState(
              title: 'Médecin introuvable',
              message: 'Le formulaire de réservation attend un médecin valide.',
            );
          }

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                item.fullName,
                style: Theme.of(context).textTheme.displaySmall,
              ),
              const SizedBox(height: AppSizes.xs),
              Text(
                item.specialty.name,
                style: Theme.of(context).textTheme.bodyLarge,
              ),
              AppSizes.sectionSpacing,
              Card(
                child: Padding(
                  padding: AppSizes.cardPadding,
                  child: TableCalendar<dynamic>(
                    locale: 'fr_FR',
                    firstDay: DateTime.now(),
                    lastDay: DateTime.now().add(const Duration(days: 90)),
                    focusedDay: _selectedDay,
                    selectedDayPredicate: (day) => isSameDay(day, _selectedDay),
                    headerStyle:
                        const HeaderStyle(formatButtonVisible: false),
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
                'Choisissez un créneau',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: AppSizes.md),
              slots.when(
                data: (items) {
                  if (items.isEmpty) {
                    return const EmptyState(
                      title: 'Aucun créneau disponible',
                      message:
                          'Sélectionnez une autre date pour voir les disponibilités.',
                    );
                  }
                  return TimeSlotGrid(
                    slots: items,
                    selectedSlot: _selectedSlot,
                    onSelected: (slot) => setState(() => _selectedSlot = slot),
                  );
                },
                error: (_, _) => const EmptyState(
                  title: 'Impossible de charger les créneaux',
                  message: 'Vérifiez votre connexion et réessayez.',
                ),
                loading: () => const SkeletonLoader(lines: 3),
              ),
              AppSizes.sectionSpacing,
              TextField(
                controller: _reasonController,
                minLines: 3,
                maxLines: 5,
                decoration: const InputDecoration(
                  labelText: 'Motif du rendez-vous (facultatif)',
                ),
              ),
              const SizedBox(height: AppSizes.xl),
              ElevatedButton(
                onPressed: booking.isLoading ? null : _submit,
                child: Text(
                  booking.isLoading
                      ? 'Envoi en cours…'
                      : 'Confirmer la demande',
                ),
              ),
            ],
          );
        },
        error: (_, _) => const EmptyState(
          title: 'Réservation indisponible',
          message: 'Impossible de charger le profil du médecin.',
        ),
        loading: () => const SkeletonLoader(lines: 8),
      ),
    );
  }
}
