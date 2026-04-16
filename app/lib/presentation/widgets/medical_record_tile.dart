import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/material.dart';

import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/utils/date_formatter.dart';
import '../../data/models/consultation_model.dart';

class MedicalRecordTile extends StatelessWidget {
  const MedicalRecordTile({super.key, required this.consultation, this.onTap});

  final ConsultationModel consultation;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final prescriptionLabel = consultation.prescription != null
        ? '${consultation.prescription!.items.length} médicament(s)'
        : consultation.hasPrescription
        ? 'Ordonnance disponible'
        : 'Sans ordonnance';

    return Card(
      child: ListTile(
        onTap: onTap,
        contentPadding: AppSizes.cardPadding,
        leading: CircleAvatar(
          backgroundColor: AppColors.infoSoft,
          foregroundColor: AppColors.primary,
          child: const Icon(BootstrapIcons.journal_medical),
        ),
        title: Text(consultation.medecin.fullName),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: AppSizes.xs),
          child: Text(
            '${DateFormatter.fullDateTime(consultation.completedAt)}\n'
            '${consultation.diagnosis}\n'
          ),
        ),
        trailing: const Icon(BootstrapIcons.chevron_right),
      ),
    );
  }
}
