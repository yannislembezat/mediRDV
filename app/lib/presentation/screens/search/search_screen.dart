import 'package:bootstrap_icons/bootstrap_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/constants/app_sizes.dart';
import '../../../core/router/app_router.dart';
import '../../providers/medecin_provider.dart';
import '../../widgets/app_bottom_navigation.dart';
import '../../widgets/app_scaffold.dart';
import '../../widgets/doctor_card.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/skeleton_loader.dart';

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final searchState = ref.watch(doctorSearchControllerProvider);

    return AppScaffold(
      title: 'Recherche médecins',
      bottomNavigationBar: const AppBottomNavigation(currentIndex: 1),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          TextField(
            controller: _searchController,
            onChanged: (value) {
              ref
                  .read(doctorSearchControllerProvider.notifier)
                  .updateQuery(value);
            },
            decoration: InputDecoration(
              labelText: 'Nom, spécialité ou ville',
              prefixIcon: const Icon(BootstrapIcons.search),
              suffixIcon: searchState.query.isNotEmpty
                  ? IconButton(
                      onPressed: () {
                        _searchController.clear();
                        ref
                            .read(doctorSearchControllerProvider.notifier)
                            .updateQuery('');
                      },
                      icon: const Icon(BootstrapIcons.x_lg),
                    )
                  : null,
            ),
          ),
          AppSizes.sectionSpacing,
          searchState.results.when(
            data: (items) {
              if (items.isEmpty) {
                return const EmptyState(
                  title: 'Aucun médecin trouvé',
                  message:
                      'Essayez un autre nom ou spécialité.',
                );
              }
              return Column(
                children: items
                    .map((medecin) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: AppSizes.md),
                        child: DoctorCard(
                          medecin: medecin,
                          onTap: () =>
                              context.push(AppRoutes.doctorDetail(medecin.id)),
                          onBook: () =>
                              context.push(AppRoutes.bookDoctor(medecin.id)),
                        ),
                      );
                    })
                    .toList(growable: false),
              );
            },
            error: (error, stackTrace) => EmptyState(
              title: 'Recherche indisponible',
              message: 'Impossible de contacter le serveur. Réessayez plus tard.',
              actionLabel: 'Réessayer',
              onActionPressed: () =>
                  ref.read(doctorSearchControllerProvider.notifier).refresh(),
            ),
            loading: () => const SkeletonLoader(lines: 6),
          ),
        ],
      ),
    );
  }
}
