import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../constants/app_colors.dart';

abstract final class AppTextStyles {
  static TextTheme textTheme() {
    final bodyBase = GoogleFonts.inter(
      color: AppColors.text,
      fontSize: 14,
      fontWeight: FontWeight.w400,
      height: 1.5,
    );

    return TextTheme(
      displaySmall: GoogleFonts.poppins(
        color: AppColors.primaryDark,
        fontSize: 24,
        fontWeight: FontWeight.w700,
        height: 1.2,
      ),
      headlineMedium: GoogleFonts.poppins(
        color: AppColors.primaryDark,
        fontSize: 20,
        fontWeight: FontWeight.w600,
        height: 1.25,
      ),
      headlineSmall: GoogleFonts.poppins(
        color: AppColors.primaryDark,
        fontSize: 16,
        fontWeight: FontWeight.w600,
        height: 1.3,
      ),
      bodyLarge: bodyBase,
      bodyMedium: bodyBase,
      bodySmall: bodyBase.copyWith(fontSize: 12, color: AppColors.textMuted),
      labelLarge: GoogleFonts.poppins(
        color: AppColors.surface,
        fontSize: 14,
        fontWeight: FontWeight.w500,
      ),
      labelMedium: GoogleFonts.poppins(
        color: AppColors.primaryDark,
        fontSize: 13,
        fontWeight: FontWeight.w500,
      ),
    );
  }
}
