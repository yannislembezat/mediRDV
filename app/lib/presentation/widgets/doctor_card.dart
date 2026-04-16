import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../data/models/medecin_model.dart';
import 'specialty_chip.dart';

class DoctorCard extends StatelessWidget {
  const DoctorCard({super.key, required this.medecin, this.onTap, this.onBook});

  final MedecinModel medecin;
  final VoidCallback? onTap;
  final VoidCallback? onBook;

  @override
  Widget build(BuildContext context) {
    final content = Padding(
      padding: AppSizes.cardPadding,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _Avatar(medecin: medecin),
              const SizedBox(width: AppSizes.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      medecin.fullName,
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: AppSizes.xs),
                    SpecialtyChip(specialty: medecin.specialty),
                    const SizedBox(height: AppSizes.sm),
                    Row(
                      children: [
                        const Icon(
                          BootstrapIcons.geo_alt,
                          size: AppSizes.iconSm,
                          color: AppColors.textMuted,
                        ),
                        const SizedBox(width: AppSizes.xs),
                        Expanded(
                          child: Text(
                            medecin.city,
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          AppSizes.itemSpacing,
          Text(medecin.bio, style: Theme.of(context).textTheme.bodyLarge),
          const SizedBox(height: AppSizes.md),
          Row(
            children: [
              _InfoPill(
                icon: BootstrapIcons.person_badge,
                label: '${medecin.experienceYears} ans',
              ),
              const SizedBox(width: AppSizes.sm),
              _InfoPill(
                icon: BootstrapIcons.clipboard2_pulse,
                label: '${medecin.consultationFee} €',
              ),
            ],
          ),
          if (onBook != null) ...[
            const SizedBox(height: AppSizes.lg),
            ElevatedButton(
              onPressed: onBook,
              child: const Text('Réserver ce médecin'),
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

class _Avatar extends StatelessWidget {
  const _Avatar({required this.medecin});

  final MedecinModel medecin;

  @override
  Widget build(BuildContext context) {
    if (medecin.avatarUrl == null) {
      return CircleAvatar(
        radius: 28,
        backgroundColor: AppColors.infoSoft,
        foregroundColor: AppColors.primary,
        child: Text(medecin.initials),
      );
    }

    return ClipOval(
      child: CachedNetworkImage(
        imageUrl: medecin.avatarUrl!,
        width: 56,
        height: 56,
        fit: BoxFit.cover,
        placeholder: (context, url) =>
            Container(width: 56, height: 56, color: AppColors.infoSoft),
        errorWidget: (context, url, error) => CircleAvatar(
          radius: 28,
          backgroundColor: AppColors.infoSoft,
          foregroundColor: AppColors.primary,
          child: Text(medecin.initials),
        ),
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  const _InfoPill({required this.icon, required this.label});

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
        color: AppColors.background,
        borderRadius: BorderRadius.circular(AppSizes.radiusLg),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: AppSizes.iconSm, color: AppColors.primaryDark),
          const SizedBox(width: AppSizes.xs),
          Text(label, style: Theme.of(context).textTheme.bodySmall),
        ],
      ),
    );
  }
}
