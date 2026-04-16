import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/notification_model.dart';
import '../../data/repositories/notification_repository.dart';

// ---------------------------------------------------------------------------
// Notification state
// ---------------------------------------------------------------------------

class NotificationState {
  const NotificationState({
    this.items = const [],
    this.unreadCount = 0,
    this.isLoading = false,
    this.errorMessage,
  });

  final List<NotificationModel> items;
  final int unreadCount;
  final bool isLoading;
  final String? errorMessage;

  NotificationState copyWith({
    List<NotificationModel>? items,
    int? unreadCount,
    bool? isLoading,
    String? errorMessage,
    bool clearError = false,
  }) {
    return NotificationState(
      items: items ?? this.items,
      unreadCount: unreadCount ?? this.unreadCount,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: clearError ? null : errorMessage ?? this.errorMessage,
    );
  }
}

final notificationControllerProvider =
    NotifierProvider<NotificationController, NotificationState>(
      NotificationController.new,
    );

/// Convenience provider — number of unread notifications for badge display.
final unreadNotificationCountProvider = Provider<int>((ref) {
  return ref.watch(notificationControllerProvider).unreadCount;
});

// ---------------------------------------------------------------------------
// Controller
// ---------------------------------------------------------------------------

class NotificationController extends Notifier<NotificationState> {
  late final NotificationRepository _repository;

  @override
  NotificationState build() {
    _repository = ref.read(notificationRepositoryProvider);
    Future<void>.microtask(refresh);
    return const NotificationState(isLoading: true);
  }

  Future<void> refresh() async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final result = await _repository.fetchNotifications();
      state = NotificationState(
        items: result.items,
        unreadCount: result.unreadCount,
      );
    } catch (error) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Impossible de charger les notifications.',
      );
    }
  }

  Future<void> markAsRead(String notificationId) async {
    try {
      final updated = await _repository.markAsRead(notificationId);
      final newItems = state.items.map((n) {
        return n.id == notificationId ? updated : n;
      }).toList(growable: false);
      final unread = newItems.where((n) => !n.isRead).length;
      state = state.copyWith(items: newItems, unreadCount: unread);
    } catch (_) {
      // Non-critical: silently ignore mark-as-read failures.
    }
  }
}
