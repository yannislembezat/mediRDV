import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/notification_model.dart';

final notificationRepositoryProvider = Provider<NotificationRepository>((ref) {
  return NotificationRepository(dio: ref.read(dioProvider));
});

class NotificationRepository {
  const NotificationRepository({required this.dio});

  final Dio dio;

  Future<({List<NotificationModel> items, int unreadCount})> fetchNotifications({
    bool? isRead,
    int limit = 20,
  }) async {
    final response = await dio.get<Map<String, dynamic>>(
      ApiEndpoints.notifications,
      queryParameters: {
        'isRead': isRead,
        'limit': limit,
      }..removeWhere((_, v) => v == null),
    );
    final items = (response.data?['data'] as List<dynamic>?) ?? [];
    final unreadCount = (response.data?['unreadCount'] as num?)?.toInt() ?? 0;
    return (
      items: items
          .cast<Map<String, dynamic>>()
          .map(NotificationModel.fromJson)
          .toList(growable: false),
      unreadCount: unreadCount,
    );
  }

  Future<NotificationModel> markAsRead(String notificationId) async {
    final response = await dio.patch<Map<String, dynamic>>(
      ApiEndpoints.notificationMarkRead(notificationId),
    );
    final data = response.data?['data'] as Map<String, dynamic>;
    return NotificationModel.fromJson(data);
  }
}
