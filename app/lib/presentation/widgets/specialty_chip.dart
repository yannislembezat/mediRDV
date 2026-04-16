import 'package:flutter/material.dart';

import '../../core/constants/app_sizes.dart';
import '../../data/models/specialty_model.dart';

class SpecialtyChip extends StatelessWidget {
  const SpecialtyChip({super.key, required this.specialty});

  final SpecialtyModel specialty;

  @override
  Widget build(BuildContext context) {
    return Chip(
      avatar: Icon(
        specialty.icon,
        color: specialty.accentColor,
        size: AppSizes.iconSm,
      ),
      backgroundColor: specialty.accentColor.withAlpha(26),
      label: Text(specialty.name),
    );
  }
}
