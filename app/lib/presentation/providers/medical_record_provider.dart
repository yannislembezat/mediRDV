import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/consultation_model.dart';
import '../../data/repositories/medical_record_repository.dart';

final medicalRecordListProvider = FutureProvider<List<ConsultationModel>>((
  ref,
) {
  return ref.read(medicalRecordRepositoryProvider).fetchMedicalRecords();
});

final medicalRecordDetailProvider =
    FutureProvider.family<ConsultationModel?, String>((ref, consultationId) {
      return ref.read(medicalRecordRepositoryProvider).findById(consultationId);
    });
