class PrescriptionItemModel {
  const PrescriptionItemModel({
    required this.medicationName,
    required this.dosage,
    required this.instructions,
    required this.duration,
    this.frequency,
  });

  final String medicationName;
  final String dosage;
  final String instructions;
  final String duration;
  final String? frequency;

  String get displayName =>
      medicationName.trim().isNotEmpty ? medicationName.trim() : 'Médicament non renseigné';

  bool get hasDosage => dosage.trim().isNotEmpty;
  bool get hasInstructions => instructions.trim().isNotEmpty;
  bool get hasDuration => duration.trim().isNotEmpty;
  bool get hasFrequency => (frequency ?? '').trim().isNotEmpty;

  factory PrescriptionItemModel.fromJson(Map<String, dynamic> json) {
    return PrescriptionItemModel(
      medicationName: json['name'] as String? ?? '',
      dosage: json['dosage'] as String? ?? '',
      instructions: json['instructions'] as String? ?? '',
      duration: json['duration'] as String? ?? '',
      frequency: json['frequency'] as String?,
    );
  }
}

class PrescriptionModel {
  const PrescriptionModel({
    required this.id,
    required this.issuedAt,
    required this.items,
    this.notes,
  });

  final String id;
  final DateTime issuedAt;
  final List<PrescriptionItemModel> items;
  final String? notes;

  bool get hasItems => items.isNotEmpty;

  factory PrescriptionModel.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List<dynamic>? ?? [];
    return PrescriptionModel(
      id: json['id'].toString(),
      issuedAt: DateTime.tryParse(json['createdAt'] as String? ?? '') ?? DateTime.now(),
      notes: json['notes'] as String?,
      items: rawItems
          .cast<Map<String, dynamic>>()
          .map(PrescriptionItemModel.fromJson)
          .toList(growable: false),
    );
  }
}
