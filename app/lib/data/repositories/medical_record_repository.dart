import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/consultation_model.dart';

final medicalRecordRepositoryProvider = Provider<MedicalRecordRepository>((ref) {
  return MedicalRecordRepository(dio: ref.read(dioProvider));
});

class MedicalRecordRepository {
  const MedicalRecordRepository({required this.dio});

  final Dio dio;

  Future<List<ConsultationModel>> fetchMedicalRecords() async {
    final response = await dio.get<Map<String, dynamic>>(ApiEndpoints.medicalRecords);
    final items = (response.data?['data'] as List<dynamic>?) ?? [];
    return items
        .cast<Map<String, dynamic>>()
        .map(ConsultationModel.fromSummaryJson)
        .toList(growable: false);
  }

  Future<ConsultationModel?> findById(String consultationId) async {
    final response = await dio.get<Map<String, dynamic>>(
      ApiEndpoints.medicalRecordDetail(consultationId),
    );
    final data = response.data?['data'] as Map<String, dynamic>?;
    return data != null ? ConsultationModel.fromJson(data) : null;
  }
}
