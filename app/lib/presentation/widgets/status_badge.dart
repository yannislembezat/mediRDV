import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/material.dart';

import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../data/models/appointment_model.dart';

class StatusBadge extends StatelessWidget {
  const StatusBadge({super.key, required this.status});

  final AppointmentStatus status;

  @override
  Widget build(BuildContext context) {
    final config = switch (status) {
      AppointmentStatus.pending => _StatusConfig(
        label: 'En attente',
        color: AppColors.warning,
        background: AppColors.warningSoft,
        icon: BootstrapIcons.hourglass_split,
      ),
      AppointmentStatus.confirmed => _StatusConfig(
        label: 'Confirmé',
        color: AppColors.accent,
        background: AppColors.successSoft,
        icon: BootstrapIcons.calendar_check,
      ),
      AppointmentStatus.refused => _StatusConfig(
        label: 'Refusé',
        color: AppColors.danger,
        background: AppColors.dangerSoft,
        icon: BootstrapIcons.x_circle,
      ),
      AppointmentStatus.completed => _StatusConfig(
        label: 'Complété',
        color: AppColors.primaryDark,
        background: AppColors.infoSoft,
        icon: BootstrapIcons.check_circle,
      ),
      AppointmentStatus.cancelled => _StatusConfig(
        label: 'Annulé',
        color: AppColors.textMuted,
        background: AppColors.border,
        icon: BootstrapIcons.slash_circle,
      ),
    };

    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppSizes.sm,
        vertical: AppSizes.xs,
      ),
      decoration: BoxDecoration(
        color: config.background,
        borderRadius: BorderRadius.circular(AppSizes.radiusLg),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(config.icon, size: AppSizes.iconSm, color: config.color),
          const SizedBox(width: AppSizes.xs),
          Text(
            config.label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: config.color,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusConfig {
  const _StatusConfig({
    required this.label,
    required this.color,
    required this.background,
    required this.icon,
  });

  final String label;
  final Color color;
  final Color background;
  final IconData icon;
}
