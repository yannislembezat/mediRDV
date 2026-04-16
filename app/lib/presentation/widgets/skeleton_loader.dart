import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';

class SkeletonLoader extends StatelessWidget {
  const SkeletonLoader({super.key, this.lines = 3});

  final int lines;

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: AppColors.border,
      highlightColor: AppColors.surface,
      child: Column(
        children: List.generate(
          lines,
          (index) => Padding(
            padding: const EdgeInsets.only(bottom: AppSizes.sm),
            child: Container(
              height: 16,
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppSizes.radiusSm),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
