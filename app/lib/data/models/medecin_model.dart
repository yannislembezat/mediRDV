import 'package:bootstrap_icons/bootstrap_icons.dart';

import '../../core/constants/app_colors.dart';
import 'specialty_model.dart';

class MedecinModel {
  const MedecinModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.specialty,
    required this.city,
    required this.bio,
    required this.experienceYears,
    required this.consultationFee,
    this.avatarUrl,
    this.nameOverride,
  });

  final String id;
  final String firstName;
  final String lastName;
  final SpecialtyModel specialty;
  final String city;
  final String bio;
  final int experienceYears;
  final int consultationFee;
  final String? avatarUrl;
  // Used when API returns a pre-formatted display name (e.g. in appointment embeds).
  final String? nameOverride;

  String get fullName => nameOverride ?? 'Dr $firstName $lastName';

  String get initials {
    if (nameOverride != null && nameOverride!.isNotEmpty) {
      final parts = nameOverride!.trim().split(RegExp(r'\s+'));
      if (parts.length >= 2) {
        return '${parts[parts.length - 2][0]}${parts[parts.length - 1][0]}'.toUpperCase();
      }
      return nameOverride![0].toUpperCase();
    }
    final f = firstName.isNotEmpty ? firstName[0] : '';
    final l = lastName.isNotEmpty ? lastName[0] : '';
    return '$f$l'.toUpperCase();
  }

  /// Builds a full [MedecinModel] from `/api/medecins` or `/api/medecins/{id}` JSON.
  factory MedecinModel.fromJson(Map<String, dynamic> json, [int colorIndex = 0]) {
    final specialtyJson = json['specialty'] as Map<String, dynamic>?;
    return MedecinModel(
      id: json['id'].toString(),
      firstName: json['firstName'] as String? ?? '',
      lastName: json['lastName'] as String? ?? '',
      city: json['officeLocation'] as String? ?? '',
      bio: json['bio'] as String? ?? '',
      experienceYears: (json['yearsExperience'] as num?)?.toInt() ?? 0,
      consultationFee: (json['consultationDuration'] as num?)?.toInt() ?? 0,
      avatarUrl: json['avatarUrl'] as String?,
      specialty: specialtyJson != null
          ? SpecialtyModel.fromJson(specialtyJson, colorIndex)
          : const SpecialtyModel(
              id: '',
              name: '',
              icon: BootstrapIcons.hospital,
              accentColor: AppColors.primary,
            ),
    );
  }

  /// Builds a minimal [MedecinModel] from the embedded doctor object in appointment responses.
  /// The API returns `{id, fullName, specialty: String, avatarUrl}` in that context.
  factory MedecinModel.fromAppointmentDoctorJson(Map<String, dynamic> json) {
    return MedecinModel(
      id: json['id'].toString(),
      firstName: '',
      lastName: '',
      nameOverride: json['fullName'] as String?,
      specialty: SpecialtyModel(
        id: '',
        name: json['specialty'] as String? ?? '',
        icon: BootstrapIcons.hospital,
        accentColor: AppColors.primary,
      ),
      city: '',
      bio: '',
      experienceYears: 0,
      consultationFee: 0,
      avatarUrl: json['avatarUrl'] as String?,
    );
  }
}
