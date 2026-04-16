import 'package:bootstrap_icons/bootstrap_icons.dart';

import '../../core/constants/app_colors.dart';
import '../models/appointment_model.dart';
import '../models/consultation_model.dart';
import '../models/medecin_model.dart';
import '../models/prescription_model.dart';
import '../models/specialty_model.dart';

abstract final class MockData {
  static const cardiology = SpecialtyModel(
    id: 'cardiologie',
    name: 'Cardiologie',
    icon: BootstrapIcons.heart_pulse,
    accentColor: AppColors.accent,
  );
  static const pediatrics = SpecialtyModel(
    id: 'pediatrie',
    name: 'Pédiatrie',
    icon: BootstrapIcons.activity,
    accentColor: AppColors.primary,
  );
  static const dermatology = SpecialtyModel(
    id: 'dermatologie',
    name: 'Dermatologie',
    icon: BootstrapIcons.shield_check,
    accentColor: AppColors.warning,
  );

  static const specialties = <SpecialtyModel>[
    cardiology,
    pediatrics,
    dermatology,
  ];

  static const medecins = <MedecinModel>[
    MedecinModel(
      id: 'med-1',
      firstName: 'Amine',
      lastName: 'Berrada',
      specialty: cardiology,
      city: 'Paris',
      bio:
          'Cardiologue interventionnel avec un suivi centré sur la prévention et la continuité des soins.',
      experienceYears: 12,
      consultationFee: 50,
      avatarUrl: 'https://i.pravatar.cc/300?img=12',
    ),
    MedecinModel(
      id: 'med-2',
      firstName: 'Salma',
      lastName: 'El Idrissi',
      specialty: pediatrics,
      city: 'Lyon',
      bio:
          'Pédiatre attentive au suivi des nourrissons et à l accompagnement des familles au quotidien.',
      experienceYears: 9,
      consultationFee: 40,
      avatarUrl: 'https://i.pravatar.cc/300?img=32',
    ),
    MedecinModel(
      id: 'med-3',
      firstName: 'Youssef',
      lastName: 'Tazi',
      specialty: dermatology,
      city: 'Marseille',
      bio:
          'Dermatologue orienté prise en charge rapide avec calendrier de consultation simple à réserver.',
      experienceYears: 11,
      consultationFee: 45,
      avatarUrl: 'https://i.pravatar.cc/300?img=56',
    ),
  ];

  static final appointments = <AppointmentModel>[
    AppointmentModel(
      id: 'rdv-1',
      medecin: medecins[0],
      scheduledAt: DateTime.now().add(const Duration(days: 1, hours: 3)),
      status: AppointmentStatus.confirmed,
      reason: 'Contrôle tension et fatigue persistante',
      location: 'Centre MediRDV Paris',
      adminNote: 'Dossier validé par le secrétariat.',
      hasMedicalRecord: false,
    ),
    AppointmentModel(
      id: 'rdv-2',
      medecin: medecins[1],
      scheduledAt: DateTime.now().add(const Duration(days: 4, hours: 1)),
      status: AppointmentStatus.pending,
      reason: 'Suivi pédiatrique trimestriel',
      location: 'Cabinet Enfance Lyon',
      hasMedicalRecord: false,
    ),
    AppointmentModel(
      id: 'rdv-3',
      medecin: medecins[2],
      scheduledAt: DateTime.now().subtract(const Duration(days: 16)),
      status: AppointmentStatus.completed,
      reason: 'Suivi traitement cutané',
      location: 'Clinique Dermato Marseille',
      hasMedicalRecord: true,
    ),
  ];

  static final consultations = <ConsultationModel>[
    ConsultationModel(
      id: 'cs-1',
      medecin: medecins[2],
      completedAt: DateTime.now().subtract(const Duration(days: 16)),
      diagnosis: 'Dermatite légère stabilisée',
      notes:
          'Poursuite du traitement local pendant 14 jours, contrôle conseillé en cas de récidive.',
      hasPrescription: true,
      prescription: PrescriptionModel(
        id: 'ord-1',
        issuedAt: DateTime.now().subtract(const Duration(days: 16)),
        items: const [
          PrescriptionItemModel(
            medicationName: 'Dermacalm',
            dosage: '1 application matin et soir',
            instructions: 'Appliquer sur peau sèche après nettoyage.',
            duration: '14 jours',
          ),
        ],
      ),
    ),
  ];

  static List<DateTime> slotsFor(String medecinId, DateTime day) {
    final base = DateTime(day.year, day.month, day.day);
    final generated = <DateTime>[
      base.add(const Duration(hours: 9)),
      base.add(const Duration(hours: 10, minutes: 30)),
      base.add(const Duration(hours: 14)),
      base.add(const Duration(hours: 15, minutes: 30)),
    ];

    if (medecinId == 'med-2') {
      return generated.take(3).toList(growable: false);
    }

    return generated;
  }
}
