import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/appointment_model.dart';

final appointmentRepositoryProvider = Provider<AppointmentRepository>((ref) {
  return AppointmentRepository(dio: ref.read(dioProvider));
});

class AppointmentRepository {
  const AppointmentRepository({required this.dio});

  final Dio dio;

  Future<List<AppointmentModel>> fetchAppointments({
    List<AppointmentStatus>? statuses,
    int page = 1,
    int limit = 20,
  }) async {
    final statusParam = statuses?.map((s) => s.name).join(',');
    final response = await dio.get<Map<String, dynamic>>(
      ApiEndpoints.appointments,
      queryParameters: {
        if (statusParam != null && statusParam.isNotEmpty) 'status': statusParam,
        'page': page,
        'limit': limit,
      },
    );
    final items = (response.data?['data'] as List<dynamic>?) ?? [];
    return items
        .cast<Map<String, dynamic>>()
        .map(AppointmentModel.fromJson)
        .toList(growable: false);
  }

  Future<AppointmentModel?> fetchNextAppointment() async {
    final results = await fetchAppointments(
      statuses: [AppointmentStatus.confirmed],
      limit: 1,
    );
    return results.isNotEmpty ? results.first : null;
  }

  Future<AppointmentModel?> findById(String appointmentId) async {
    final response = await dio.get<Map<String, dynamic>>(
      ApiEndpoints.appointmentDetail(appointmentId),
    );
    final data = response.data?['data'] as Map<String, dynamic>?;
    return data != null ? AppointmentModel.fromJson(data) : null;
  }

  Future<AppointmentModel> createAppointment({
    required String medecinId,
    required DateTime dateTime,
    String? reason,
  }) async {
    final response = await dio.post<Map<String, dynamic>>(
      ApiEndpoints.appointments,
      data: {
        'medecinId': int.tryParse(medecinId) ?? medecinId,
        'dateTime': dateTime.toIso8601String(),
        if (reason != null && reason.isNotEmpty) 'reason': reason,
      },
    );
    final data = response.data?['data'] as Map<String, dynamic>;
    return AppointmentModel.fromJson(data);
  }

  Future<AppointmentModel> cancelAppointment(String appointmentId) async {
    final response = await dio.delete<Map<String, dynamic>>(
      ApiEndpoints.appointmentDetail(appointmentId),
    );
    final data = response.data?['data'] as Map<String, dynamic>;
    return AppointmentModel.fromJson(data);
  }
}
