import 'package:bootstrap_icons/bootstrap_icons.dart';

import '../../core/constants/app_colors.dart';
import 'medecin_model.dart';
import 'specialty_model.dart';

const _fallbackSpecialty = SpecialtyModel(
  id: '',
  name: '',
  icon: BootstrapIcons.hospital,
  accentColor: AppColors.primary,
);

enum AppointmentStatus { pending, confirmed, refused, completed, cancelled }

extension AppointmentStatusX on AppointmentStatus {
  static AppointmentStatus fromString(String value) {
    return switch (value) {
      'pending' => AppointmentStatus.pending,
      'confirmed' => AppointmentStatus.confirmed,
      'refused' => AppointmentStatus.refused,
      'completed' => AppointmentStatus.completed,
      'cancelled' => AppointmentStatus.cancelled,
      _ => AppointmentStatus.pending,
    };
  }
}

class AppointmentModel {
  const AppointmentModel({
    required this.id,
    required this.medecin,
    required this.scheduledAt,
    required this.status,
    required this.reason,
    required this.location,
    this.adminNote,
    this.hasMedicalRecord = false,
    this.canCancel = false,
    this.consultationId,
  });

  final String id;
  final MedecinModel medecin;
  final DateTime scheduledAt;
  final AppointmentStatus status;
  final String reason;
  final String location;
  final String? adminNote;
  final bool hasMedicalRecord;
  final bool canCancel;
  final String? consultationId;

  /// Builds from `/api/appointments` list or `/api/appointments/{id}` detail JSON.
  factory AppointmentModel.fromJson(Map<String, dynamic> json) {
    final medecinJson = json['medecin'] as Map<String, dynamic>?;
    final statusStr = json['status'] as String? ?? 'pending';
    final consultationId = json['consultationId'];

    return AppointmentModel(
      id: json['id'].toString(),
      scheduledAt: DateTime.tryParse(json['dateTime'] as String? ?? '') ?? DateTime.now(),
      status: AppointmentStatusX.fromString(statusStr),
      reason: json['reason'] as String? ?? '',
      location: medecinJson?['officeLocation'] as String? ?? '',
      adminNote: json['adminNote'] as String?,
      hasMedicalRecord: consultationId != null,
      canCancel: json['canCancel'] as bool? ?? false,
      consultationId: consultationId?.toString(),
      medecin: medecinJson != null
          ? MedecinModel.fromAppointmentDoctorJson(medecinJson)
          : const MedecinModel(
              id: '',
              firstName: '',
              lastName: '',
              specialty: _fallbackSpecialty,
              city: '',
              bio: '',
              experienceYears: 0,
              consultationFee: 0,
            ),
    );
  }
}
