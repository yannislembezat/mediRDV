import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/widgets.dart';

import '../../core/constants/app_colors.dart';

class SpecialtyModel {
  const SpecialtyModel({
    required this.id,
    required this.name,
    required this.icon,
    required this.accentColor,
    this.slug,
    this.description,
  });

  final String id;
  final String name;
  final IconData icon;
  final Color accentColor;
  final String? slug;
  final String? description;

  factory SpecialtyModel.fromJson(Map<String, dynamic> json, [int colorIndex = 0]) {
    return SpecialtyModel(
      id: json['id'].toString(),
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String?,
      description: json['description'] as String?,
      icon: _iconFromSlug(json['icon'] as String?),
      accentColor: _colorFromIndex(colorIndex),
    );
  }

  static IconData _iconFromSlug(String? slug) {
    return switch (slug) {
      'heart-pulse' => BootstrapIcons.heart_pulse,
      'activity' => BootstrapIcons.activity,
      'shield-check' => BootstrapIcons.shield_check,
      'eye' => BootstrapIcons.eye,
      'wind' => BootstrapIcons.wind,
      'person-walking' => BootstrapIcons.person_walking,
      'lightbulb' => BootstrapIcons.lightbulb,
      _ => BootstrapIcons.hospital,
    };
  }

  static Color _colorFromIndex(int index) {
    const colors = <Color>[
      AppColors.accent,
      AppColors.primary,
      AppColors.warning,
      AppColors.primaryDark,
      AppColors.danger,
    ];
    return colors[index % colors.length];
  }
}
