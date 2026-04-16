import 'package:bootstrap_icons/bootstrap_icons.dart';

import '../../core/constants/app_colors.dart';
import 'medecin_model.dart';
import 'prescription_model.dart';
import 'specialty_model.dart';

const _unknownSpecialty = SpecialtyModel(
  id: '',
  name: '',
  icon: BootstrapIcons.hospital,
  accentColor: AppColors.primary,
);

class ConsultationModel {
  const ConsultationModel({
    required this.id,
    required this.medecin,
    required this.completedAt,
    required this.diagnosis,
    required this.notes,
    required this.hasPrescription,
    this.prescription,
    this.vitalSigns,
  });

  final String id;
  final MedecinModel medecin;
  final DateTime completedAt;
  final String diagnosis;
  final String notes;
  final bool hasPrescription;
  final PrescriptionModel? prescription;
  final Map<String, String?>? vitalSigns;

  /// Builds from `/api/medical-records/{id}` detail JSON.
  /// The API nests the doctor inside `appointment.medecin`.
  factory ConsultationModel.fromJson(Map<String, dynamic> json) {
    final appointmentJson = json['appointment'] as Map<String, dynamic>?;
    final medecinJson = appointmentJson?['medecin'] as Map<String, dynamic>?;
    final prescriptionJson = json['prescription'] as Map<String, dynamic>?;

    return ConsultationModel(
      id: json['id'].toString(),
      completedAt: DateTime.tryParse(json['completedAt'] as String? ?? '') ?? DateTime.now(),
      diagnosis: json['diagnosis'] as String? ?? '',
      notes: json['notes'] as String? ?? '',
      hasPrescription: prescriptionJson != null,
      vitalSigns: _parseVitalSigns(json['vitalSigns']),
      medecin: medecinJson != null
          ? MedecinModel.fromAppointmentDoctorJson(medecinJson)
          : const MedecinModel(
              id: '',
              firstName: '',
              lastName: '',
              specialty: _unknownSpecialty,
              city: '',
              bio: '',
              experienceYears: 0,
              consultationFee: 0,
            ),
      prescription:
          prescriptionJson != null ? PrescriptionModel.fromJson(prescriptionJson) : null,
    );
  }

  /// Builds from `/api/medical-records` list JSON (summary shape).
  factory ConsultationModel.fromSummaryJson(Map<String, dynamic> json) {
    final appointmentJson = json['appointment'] as Map<String, dynamic>?;
    final medecinJson = appointmentJson?['medecin'] as Map<String, dynamic>?;

    return ConsultationModel(
      id: json['id'].toString(),
      completedAt: DateTime.tryParse(json['completedAt'] as String? ?? '') ?? DateTime.now(),
      diagnosis: json['diagnosis'] as String? ?? '',
      notes: '',
      hasPrescription: json['hasPrescription'] as bool? ?? false,
      medecin: medecinJson != null
          ? MedecinModel.fromAppointmentDoctorJson(medecinJson)
          : const MedecinModel(
              id: '',
              firstName: '',
              lastName: '',
              specialty: _unknownSpecialty,
              city: '',
              bio: '',
              experienceYears: 0,
              consultationFee: 0,
            ),
    );
  }

  static Map<String, String?>? _parseVitalSigns(dynamic rawVitalSigns) {
    if (rawVitalSigns is! Map) {
      return null;
    }

    final vitalSigns = <String, String?>{};

    for (final entry in rawVitalSigns.entries) {
      final key = entry.key;
      if (key is! String || key.trim().isEmpty) {
        continue;
      }

      vitalSigns[key.trim()] = entry.value?.toString();
    }

    return vitalSigns.isEmpty ? null : vitalSigns;
  }
}
