import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/constants/app_colors.dart';
import '../../../core/constants/app_sizes.dart';
import '../../../data/models/appointment_model.dart';
import '../../../data/models/notification_model.dart';
import '../../providers/notification_provider.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/skeleton_loader.dart';
import '../../widgets/status_badge.dart';

class NotificationScreen extends ConsumerWidget {
  const NotificationScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(notificationControllerProvider);
    final Widget body;

    if (state.isLoading) {
      body = const SkeletonLoader(lines: 5);
    } else if (state.errorMessage != null) {
      body = Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(
            BootstrapIcons.exclamation_triangle,
            size: 48,
            color: AppColors.warning,
          ),
          const SizedBox(height: AppSizes.md),
          Text(
            state.errorMessage!,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodyLarge,
          ),
          const SizedBox(height: AppSizes.md),
          ElevatedButton.icon(
            onPressed: () {
              ref.read(notificationControllerProvider.notifier).refresh();
            },
            icon: const Icon(BootstrapIcons.arrow_clockwise),
            label: const Text('Réessayer'),
          ),
        ],
      );
    } else if (state.items.isEmpty) {
      body = const EmptyState(
        title: 'Aucune notification',
        message: 'Vous n\'avez pas de notifications pour le moment.',
      );
    } else {
      body = Column(
        children: [
          for (var index = 0; index < state.items.length; index++) ...[
            if (index > 0) const SizedBox(height: AppSizes.sm),
            NotificationTile(
              notification: state.items[index],
              onTap: () {
                final notification = state.items[index];
                if (!notification.isRead) {
                  ref
                      .read(notificationControllerProvider.notifier)
                      .markAsRead(notification.id);
                }
              },
            ),
          ],
        ],
      );
    }

    return AppScaffold(
      title: 'Notifications',
      actions: [
        IconButton(
          onPressed: state.isLoading
              ? null
              : () {
                  ref.read(notificationControllerProvider.notifier).refresh();
                },
          icon: const Icon(BootstrapIcons.arrow_clockwise),
          tooltip: 'Actualiser',
        ),
        if (state.unreadCount > 0)
          TextButton.icon(
            onPressed: state.isLoading
                ? null
                : () {
                    for (final notification in state.items) {
                      if (!notification.isRead) {
                        ref
                            .read(notificationControllerProvider.notifier)
                            .markAsRead(notification.id);
                      }
                    }
                  },
            icon: const Icon(BootstrapIcons.check2_all, size: 18),
            label: const Text('Tout lire'),
          ),
      ],
      child: body,
    );
  }
}

class NotificationTile extends StatelessWidget {
  const NotificationTile({super.key, required this.notification, this.onTap});

  final NotificationModel notification;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final isRead = notification.isRead;

    return Card(
      color: isRead ? null : AppColors.infoSoft,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        child: Padding(
          padding: AppSizes.cardPadding,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      notification.title,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: isRead
                            ? FontWeight.normal
                            : FontWeight.bold,
                      ),
                    ),
                  ),
                  if (notification.typeLabel != null)
                    StatusBadge(
                      status: _mapNotificationType(notification.type),
                    ),
                ],
              ),
              const SizedBox(height: AppSizes.xs),
              Text(
                notification.body,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: isRead ? AppColors.textMuted : null,
                ),
              ),
              const SizedBox(height: AppSizes.sm),
              Row(
                children: [
                  const Icon(BootstrapIcons.clock, color: AppColors.textMuted),
                  const SizedBox(width: AppSizes.xs),
                  Text(
                    notification.createdAt.toLocal().toString(),
                    style: Theme.of(
                      context,
                    ).textTheme.bodySmall?.copyWith(color: AppColors.textMuted),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  AppointmentStatus _mapNotificationType(String type) {
    return switch (type.toLowerCase()) {
      'appointment_confirmed' => AppointmentStatus.confirmed,
      'appointment_refused' => AppointmentStatus.refused,
      'appointment_pending' => AppointmentStatus.pending,
      'consultation_ready' => AppointmentStatus.completed,
      _ => AppointmentStatus.pending,
    };
  }
}
