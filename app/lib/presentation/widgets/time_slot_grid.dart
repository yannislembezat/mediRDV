import 'package:flutter/material.dart';

import '../../core/constants/app_sizes.dart';
import '../../core/utils/date_formatter.dart';

class TimeSlotGrid extends StatelessWidget {
  const TimeSlotGrid({
    super.key,
    required this.slots,
    required this.selectedSlot,
    required this.onSelected,
  });

  final List<DateTime> slots;
  final DateTime? selectedSlot;
  final ValueChanged<DateTime> onSelected;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: AppSizes.sm,
      runSpacing: AppSizes.sm,
      children: slots
          .map((slot) {
            final isSelected = slot == selectedSlot;
            return ChoiceChip(
              label: Text(DateFormatter.time(slot)),
              selected: isSelected,
              onSelected: (selected) => onSelected(slot),
            );
          })
          .toList(growable: false),
    );
  }
}
