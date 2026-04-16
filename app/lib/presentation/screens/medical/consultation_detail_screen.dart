import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_sizes.dart';
import '../../../core/utils/date_formatter.dart';
import '../../../data/models/prescription_model.dart';
import '../../providers/medical_record_provider.dart';
import '../../widgets/app_bottom_navigation.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/empty_state.dart';

class ConsultationDetailScreen extends ConsumerWidget {
  const ConsultationDetailScreen({super.key, required this.consultationId});

  final String consultationId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final consultation = ref.watch(medicalRecordDetailProvider(consultationId));

    return AppScaffold(
      title: 'Détail consultation',
      bottomNavigationBar: const AppBottomNavigation(currentIndex: 3),
      child: consultation.when(
        data: (item) {
          if (item == null) {
            return const EmptyState(
              title: 'Consultation introuvable',
              message:
                  'Cette consultation n\'est pas disponible.',
            );
          }

          final prescriptionItems =
              item.prescription?.items
                  .where(
                    (line) =>
                        line.medicationName.trim().isNotEmpty ||
                        line.hasDosage ||
                        line.hasDuration ||
                        line.hasFrequency ||
                        line.hasInstructions,
                  )
                  .toList(growable: false) ??
              const <PrescriptionItemModel>[];

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: double.infinity,
                padding: AppSizes.cardPadding,
                decoration: BoxDecoration(
                  color: AppColors.primaryDark,
                  borderRadius: BorderRadius.circular(AppSizes.radiusLg),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.medecin.fullName,
                      style: Theme.of(context).textTheme.displaySmall?.copyWith(
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: AppSizes.xs),
                    Text(
                      item.medecin.specialty.name,
                      style: Theme.of(
                        context,
                      ).textTheme.bodyLarge?.copyWith(color: Colors.white70),
                    ),
                    const SizedBox(height: AppSizes.md),
                    Wrap(
                      spacing: AppSizes.sm,
                      runSpacing: AppSizes.sm,
                      children: [
                        _MetaChip(
                          icon: BootstrapIcons.calendar3,
                          label: DateFormatter.fullDateTime(item.completedAt),
                        ),
                        _MetaChip(
                          icon: BootstrapIcons.capsule,
                          label: prescriptionItems.isNotEmpty
                              ? '${prescriptionItems.length} médicament(s)'
                              : item.hasPrescription
                              ? 'Ordonnance associée'
                              : 'Sans ordonnance',
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSizes.md),
              _SectionCard(
                title: 'Diagnostic',
                icon: BootstrapIcons.clipboard2_pulse,
                child: Text(
                  item.diagnosis.trim().isNotEmpty
                      ? item.diagnosis
                      : 'Diagnostic non renseigné.',
                ),
              ),
              _SectionCard(
                title: 'Notes du médecin',
                icon: BootstrapIcons.journal_text,
                child: Text(
                  item.notes.trim().isNotEmpty
                      ? item.notes
                      : 'Aucune note clinique disponible.',
                ),
              ),
              if (item.vitalSigns != null && item.vitalSigns!.isNotEmpty)
                _SectionCard(
                  title: 'Constantes vitales',
                  icon: BootstrapIcons.activity,
                  child: Wrap(
                    spacing: AppSizes.sm,
                    runSpacing: AppSizes.sm,
                    children: item.vitalSigns!.entries.map((entry) {
                      final value = entry.value?.trim();
                      return Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: AppSizes.sm,
                          vertical: AppSizes.xs,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.infoSoft,
                          borderRadius: BorderRadius.circular(AppSizes.radiusSm),
                        ),
                        child: Text(
                          '${_formatVitalLabel(entry.key)}: ${value != null && value.isNotEmpty ? value : 'Non renseigné'}',
                        ),
                      );
                    }).toList(growable: false),
                  ),
                ),
              _SectionCard(
                title: 'Traitement',
                icon: BootstrapIcons.capsule,
                child: prescriptionItems.isEmpty
                    ? Text(
                        item.hasPrescription
                            ? 'Ordonnance enregistrée sans ligne détaillée.'
                            : 'Aucune ordonnance associée.',
                      )
                    : Column(
                        children: prescriptionItems.map((line) {
                          return Padding(
                            padding: const EdgeInsets.only(bottom: AppSizes.md),
                            child: _PrescriptionLineCard(line: line),
                          );
                        }).toList(growable: false),
                      ),
              ),
              if ((item.prescription?.notes ?? '').trim().isNotEmpty)
                _SectionCard(
                  title: 'Consignes complémentaires',
                  icon: BootstrapIcons.chat_left_text,
                  child: Text(item.prescription!.notes!.trim()),
                ),
            ],
          );
        },
        error: (error, stackTrace) => const EmptyState(
          title: 'Consultation indisponible',
          message:
              'Impossible de charger les détails. Veuillez réessayer.',
        ),
        loading: () => const CircularProgressIndicator(),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.title,
    required this.icon,
    required this.child,
  });

  final String title;
  final IconData icon;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppSizes.md),
      child: Padding(
        padding: AppSizes.cardPadding,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: AppSizes.iconMd, color: AppColors.primaryDark),
                const SizedBox(width: AppSizes.sm),
                Expanded(
                  child: Text(title, style: Theme.of(context).textTheme.headlineSmall),
                ),
              ],
            ),
            const SizedBox(height: AppSizes.sm),
            child,
          ],
        ),
      ),
    );
  }
}

class _MetaChip extends StatelessWidget {
  const _MetaChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppSizes.sm,
        vertical: AppSizes.xs,
      ),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.14),
        borderRadius: BorderRadius.circular(AppSizes.radiusSm),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: AppSizes.iconSm, color: Colors.white),
          const SizedBox(width: AppSizes.xs),
          Text(
            label,
            style: Theme.of(
              context,
            ).textTheme.bodySmall?.copyWith(color: Colors.white),
          ),
        ],
      ),
    );
  }
}

class _PrescriptionLineCard extends StatelessWidget {
  const _PrescriptionLineCard({required this.line});

  final PrescriptionItemModel line;

  @override
  Widget build(BuildContext context) {
    final details = <String>[
      if (line.hasDosage) 'Posologie: ${line.dosage}',
      if (line.hasFrequency) 'Fréquence: ${line.frequency}',
      if (line.hasDuration) 'Durée: ${line.duration}',
      if (line.hasInstructions) 'Instructions: ${line.instructions}',
    ];

    return Container(
      width: double.infinity,
      padding: AppSizes.cardPadding,
      decoration: BoxDecoration(
        color: AppColors.infoSoft,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            line.displayName,
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          if (details.isNotEmpty) ...[
            const SizedBox(height: AppSizes.sm),
            ...details.map(
              (detail) => Padding(
                padding: const EdgeInsets.only(bottom: AppSizes.xs),
                child: Text(detail),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

String _formatVitalLabel(String key) {
  final cleaned = key.replaceAll('_', ' ').trim();
  if (cleaned.isEmpty) {
    return 'Mesure';
  }

  return cleaned[0].toUpperCase() + cleaned.substring(1);
}
