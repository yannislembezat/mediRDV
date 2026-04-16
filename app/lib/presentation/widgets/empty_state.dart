import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../../core/constants/app_sizes.dart';

class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.title,
    required this.message,
    this.assetPath = 'assets/images/medirdv_empty_state.svg',
    this.actionLabel,
    this.onActionPressed,
  });

  final String title;
  final String message;
  final String assetPath;
  final String? actionLabel;
  final VoidCallback? onActionPressed;

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return Card(
      child: Padding(
        padding: AppSizes.cardPadding,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(child: SvgPicture.asset(assetPath, height: 140)),
            AppSizes.sectionSpacing,
            Text(title, style: textTheme.headlineSmall),
            AppSizes.itemSpacing,
            Text(message, style: textTheme.bodyLarge),
            if (actionLabel != null && onActionPressed != null) ...[
              AppSizes.sectionSpacing,
              ElevatedButton(
                onPressed: onActionPressed,
                child: Text(actionLabel!),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
