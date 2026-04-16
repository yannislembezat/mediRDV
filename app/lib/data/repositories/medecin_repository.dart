import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/medecin_model.dart';
import '../models/specialty_model.dart';

final medecinRepositoryProvider = Provider<MedecinRepository>((ref) {
  return MedecinRepository(dio: ref.read(dioProvider));
});

class MedecinRepository {
  const MedecinRepository({required this.dio});

  final Dio dio;

  Future<List<SpecialtyModel>> fetchSpecialties() async {
    final response = await dio.get<Map<String, dynamic>>(ApiEndpoints.specialties);
    final items = (response.data?['data'] as List<dynamic>?) ?? [];
    return items.indexed
        .map((e) => SpecialtyModel.fromJson(e.$2 as Map<String, dynamic>, e.$1))
        .toList(growable: false);
  }

  Future<List<MedecinModel>> fetchMedecins({
    String? search,
    String? specialtyId,
  }) async {
    final response = await dio.get<Map<String, dynamic>>(
      ApiEndpoints.medecins,
      queryParameters: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (specialtyId != null && specialtyId.isNotEmpty) 'specialtyId': specialtyId,
      },
    );
    final items = (response.data?['data'] as List<dynamic>?) ?? [];
    return items.indexed
        .map((e) => MedecinModel.fromJson(e.$2 as Map<String, dynamic>, e.$1))
        .toList(growable: false);
  }

  Future<MedecinModel?> findById(String medecinId) async {
    final response = await dio.get<Map<String, dynamic>>(
      ApiEndpoints.medecinDetail(medecinId),
    );
    final data = response.data?['data'] as Map<String, dynamic>?;
    return data != null ? MedecinModel.fromJson(data) : null;
  }

  Future<List<DateTime>> fetchAvailableSlots(String medecinId, DateTime day) async {
    final response = await dio.get<Map<String, dynamic>>(
      ApiEndpoints.medecinSlots(medecinId),
      queryParameters: {'date': '${day.year.toString().padLeft(4, '0')}-'
          '${day.month.toString().padLeft(2, '0')}-'
          '${day.day.toString().padLeft(2, '0')}'},
    );
    final items = (response.data?['data'] as List<dynamic>?) ?? [];
    return items
        .cast<Map<String, dynamic>>()
        .map((slot) => DateTime.tryParse(slot['dateTime'] as String? ?? ''))
        .whereType<DateTime>()
        .toList(growable: false);
  }
}
