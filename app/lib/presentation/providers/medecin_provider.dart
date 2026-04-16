import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/medecin_model.dart';
import '../../data/models/specialty_model.dart';
import '../../data/repositories/medecin_repository.dart';

// ---------------------------------------------------------------------------
// Simple providers (used by Home screen)
// ---------------------------------------------------------------------------

final featuredSpecialtiesProvider = FutureProvider<List<SpecialtyModel>>((ref) {
  return ref.read(medecinRepositoryProvider).fetchSpecialties();
});

final medecinListProvider = FutureProvider<List<MedecinModel>>((ref) {
  return ref.read(medecinRepositoryProvider).fetchMedecins();
});

final medecinDetailProvider = FutureProvider.family<MedecinModel?, String>((
  ref,
  medecinId,
) {
  return ref.read(medecinRepositoryProvider).findById(medecinId);
});

final medecinSlotsProvider =
    FutureProvider.family<List<DateTime>, ({String medecinId, DateTime day})>((
      ref,
      params,
    ) {
      return ref
          .read(medecinRepositoryProvider)
          .fetchAvailableSlots(params.medecinId, params.day);
    });

// ---------------------------------------------------------------------------
// Search controller (used by Search screen)
// ---------------------------------------------------------------------------

class DoctorSearchState {
  const DoctorSearchState({
    this.query = '',
    this.specialtyId,
    this.results = const AsyncValue.loading(),
  });

  final String query;
  final String? specialtyId;
  final AsyncValue<List<MedecinModel>> results;

  DoctorSearchState copyWith({
    String? query,
    String? specialtyId,
    bool clearSpecialty = false,
    AsyncValue<List<MedecinModel>>? results,
  }) {
    return DoctorSearchState(
      query: query ?? this.query,
      specialtyId: clearSpecialty ? null : specialtyId ?? this.specialtyId,
      results: results ?? this.results,
    );
  }
}

final doctorSearchControllerProvider =
    NotifierProvider<DoctorSearchController, DoctorSearchState>(
      DoctorSearchController.new,
    );

class DoctorSearchController extends Notifier<DoctorSearchState> {
  late final MedecinRepository _repository;

  @override
  DoctorSearchState build() {
    _repository = ref.read(medecinRepositoryProvider);
    Future<void>.microtask(_search);
    return const DoctorSearchState();
  }

  void updateQuery(String query) {
    state = state.copyWith(
      query: query,
      results: const AsyncValue.loading(),
    );
    _search();
  }

  void updateSpecialty(String? specialtyId) {
    state = state.copyWith(
      specialtyId: specialtyId,
      clearSpecialty: specialtyId == null,
      results: const AsyncValue.loading(),
    );
    _search();
  }

  Future<void> refresh() async {
    state = state.copyWith(results: const AsyncValue.loading());
    await _search();
  }

  Future<void> _search() async {
    try {
      final results = await _repository.fetchMedecins(
        search: state.query.isEmpty ? null : state.query,
        specialtyId: state.specialtyId,
      );
      state = state.copyWith(results: AsyncValue.data(results));
    } catch (error, stackTrace) {
      state = state.copyWith(results: AsyncValue.error(error, stackTrace));
    }
  }
}
