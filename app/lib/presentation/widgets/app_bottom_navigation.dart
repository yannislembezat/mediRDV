import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/router/app_router.dart';

class AppBottomNavigation extends StatelessWidget {
  const AppBottomNavigation({super.key, required this.currentIndex});

  final int currentIndex;

  @override
  Widget build(BuildContext context) {
    return NavigationBar(
      selectedIndex: currentIndex,
      onDestinationSelected: (index) {
        final route = switch (index) {
          0 => AppRoutes.home,
          1 => AppRoutes.search,
          2 => AppRoutes.appointments,
          3 => AppRoutes.medicalRecords,
          _ => AppRoutes.profile,
        };
        context.go(route);
      },
      destinations: const [
        NavigationDestination(
          icon: Icon(BootstrapIcons.house),
          selectedIcon: Icon(BootstrapIcons.house_fill),
          label: 'Accueil',
        ),
        NavigationDestination(
          icon: Icon(BootstrapIcons.search),
          label: 'Recherche',
        ),
        NavigationDestination(
          icon: Icon(BootstrapIcons.calendar2_week),
          label: 'Mes RDV',
        ),
        NavigationDestination(
          icon: Icon(BootstrapIcons.journal_medical),
          label: 'Dossier',
        ),
        NavigationDestination(
          icon: Icon(BootstrapIcons.person),
          selectedIcon: Icon(BootstrapIcons.person_fill),
          label: 'Profil',
        ),
      ],
    );
  }
}
