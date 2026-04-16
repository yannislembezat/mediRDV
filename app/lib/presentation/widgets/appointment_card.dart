import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/material.dart';

import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/utils/date_formatter.dart';
import '../../data/models/appointment_model.dart';
import 'status_badge.dart';

class AppointmentCard extends StatelessWidget {
  const AppointmentCard({super.key, required this.appointment, this.onTap});

  final AppointmentModel appointment;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final content = Padding(
      padding: AppSizes.cardPadding,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  appointment.medecin.fullName,
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
              ),
              StatusBadge(status: appointment.status),
            ],
          ),
          const SizedBox(height: AppSizes.sm),
          Text(
            appointment.medecin.specialty.name,
            style: Theme.of(context).textTheme.bodySmall,
          ),
          const SizedBox(height: AppSizes.md),
          _InfoRow(
            icon: BootstrapIcons.calendar_event,
            label: DateFormatter.fullDateTime(appointment.scheduledAt),
          ),
          const SizedBox(height: AppSizes.sm),
          _InfoRow(icon: BootstrapIcons.geo_alt, label: appointment.location),
          const SizedBox(height: AppSizes.sm),
          _InfoRow(
            icon: BootstrapIcons.clipboard2_pulse,
            label: appointment.reason,
          ),
          if (appointment.adminNote != null) ...[
            const SizedBox(height: AppSizes.md),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(AppSizes.sm),
              decoration: BoxDecoration(
                color: AppColors.infoSoft,
                borderRadius: BorderRadius.circular(AppSizes.radiusSm),
              ),
              child: Text(
                appointment.adminNote!,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ),
          ],
        ],
      ),
    );

    return Card(
      child: onTap == null
          ? content
          : InkWell(
              borderRadius: BorderRadius.circular(AppSizes.radiusMd),
              onTap: onTap,
              child: content,
            ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(width: AppSizes.xs),
        Icon(icon, size: AppSizes.iconSm, color: AppColors.textMuted),
        const SizedBox(width: AppSizes.sm),
        Expanded(
          child: Text(label, style: Theme.of(context).textTheme.bodyLarge),
        ),
      ],
    );
  }
}
