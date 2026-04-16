import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/router/app_router.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _pageController = PageController();
  int _currentIndex = 0;

  static const _slides = [
    _OnboardingSlide(
      title: 'Trouvez rapidement le bon spécialiste',
      description:
          'Recherche par spécialité, ville et disponibilité depuis une interface claire et centrée patient.',
      icon: BootstrapIcons.search,
    ),
    _OnboardingSlide(
      title: 'Demandez vos rendez-vous en quelques étapes',
      description:
          'Calendrier lisible, créneaux visibles et suivi du statut du rendez-vous sans appel téléphonique.',
      icon: BootstrapIcons.calendar2_week,
    ),
    _OnboardingSlide(
      title: 'Retrouvez votre dossier médical au même endroit',
      description:
          'Consultez vos consultations terminées et vos ordonnances dès que le médecin finalise la visite.',
      icon: BootstrapIcons.journal_medical,
    ),
  ];

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final slide = _slides[_currentIndex];

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: AppSizes.pagePadding,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: () => context.go(AppRoutes.login),
                  child: const Text('Passer'),
                ),
              ),
              Expanded(
                child: PageView.builder(
                  controller: _pageController,
                  itemCount: _slides.length,
                  onPageChanged: (index) {
                    setState(() => _currentIndex = index);
                  },
                  itemBuilder: (context, index) {
                    final item = _slides[index];
                    return Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Center(
                          child: SvgPicture.asset(
                            'assets/images/medirdv_onboarding.svg',
                            height: 220,
                          ),
                        ),
                        const SizedBox(height: AppSizes.xl),
                        Container(
                          width: 64,
                          height: 64,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(
                              AppSizes.radiusLg,
                            ),
                            color: Theme.of(
                              context,
                            ).colorScheme.primaryContainer,
                          ),
                          child: Icon(item.icon),
                        ),
                        const SizedBox(height: AppSizes.lg),
                        Text(
                          item.title,
                          style: Theme.of(context).textTheme.displaySmall,
                        ),
                        const SizedBox(height: AppSizes.md),
                        Text(
                          item.description,
                          style: Theme.of(context).textTheme.bodyLarge,
                        ),
                      ],
                    );
                  },
                ),
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(_slides.length, (index) {
                  final isActive = index == _currentIndex;
                  return AnimatedContainer(
                    duration: const Duration(milliseconds: 250),
                    margin: const EdgeInsets.symmetric(horizontal: AppSizes.xs),
                    width: isActive ? 24 : 10,
                    height: 10,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(AppSizes.radiusLg),
                      color: isActive
                          ? Theme.of(context).colorScheme.primary
                          : Theme.of(context).colorScheme.outlineVariant,
                    ),
                  );
                }),
              ),
              const SizedBox(height: AppSizes.xl),
              Text(
                slide.title,
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: AppSizes.lg),
              ElevatedButton(
                onPressed: () => context.go(AppRoutes.login),
                child: const Text('Se connecter'),
              ),
              const SizedBox(height: AppSizes.sm),
              OutlinedButton(
                onPressed: () => context.go(AppRoutes.register),
                child: const Text('Créer un compte patient'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _OnboardingSlide {
  const _OnboardingSlide({
    required this.title,
    required this.description,
    required this.icon,
  });

  final String title;
  final String description;
  final IconData icon;
}
