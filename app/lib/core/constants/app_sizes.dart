import 'package:flutter/widgets.dart';

abstract final class AppSizes {
  static const xxs = 4.0;
  static const xs = 8.0;
  static const sm = 12.0;
  static const md = 16.0;
  static const lg = 24.0;
  static const xl = 32.0;
  static const xxl = 48.0;

  static const radiusSm = 12.0;
  static const radiusMd = 18.0;
  static const radiusLg = 24.0;

  static const iconSm = 16.0;
  static const iconMd = 20.0;
  static const iconLg = 28.0;
  static const maxContentWidth = 720.0;

  static const pagePadding = EdgeInsets.symmetric(horizontal: md, vertical: lg);
  static const cardPadding = EdgeInsets.all(md);
  static const sectionSpacing = SizedBox(height: lg);
  static const itemSpacing = SizedBox(height: md);
}
