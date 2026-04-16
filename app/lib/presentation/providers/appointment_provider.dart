import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/appointment_model.dart';
import '../../data/repositories/appointment_repository.dart';

// ---------------------------------------------------------------------------
// Read-only providers
// ---------------------------------------------------------------------------

final appointmentListProvider = FutureProvider<List<AppointmentModel>>((ref) {
  return ref.read(appointmentRepositoryProvider).fetchAppointments();
});

final nextAppointmentProvider = FutureProvider<AppointmentModel?>((ref) {
  return ref.read(appointmentRepositoryProvider).fetchNextAppointment();
});

final appointmentDetailProvider =
    FutureProvider.family<AppointmentModel?, String>((ref, appointmentId) {
      return ref.read(appointmentRepositoryProvider).findById(appointmentId);
    });

// ---------------------------------------------------------------------------
// Booking controller
// ---------------------------------------------------------------------------

class BookingState {
  const BookingState({
    this.isLoading = false,
    this.errorMessage,
    this.bookedAppointment,
  });

  final bool isLoading;
  final String? errorMessage;
  final AppointmentModel? bookedAppointment;

  bool get isSuccess => bookedAppointment != null;

  BookingState copyWith({
    bool? isLoading,
    String? errorMessage,
    AppointmentModel? bookedAppointment,
    bool clearError = false,
    bool clearBooking = false,
  }) {
    return BookingState(
      isLoading: isLoading ?? this.isLoading,
      errorMessage: clearError ? null : errorMessage ?? this.errorMessage,
      bookedAppointment:
          clearBooking ? null : bookedAppointment ?? this.bookedAppointment,
    );
  }
}

final bookingControllerProvider =
    NotifierProvider<BookingController, BookingState>(BookingController.new);

class BookingController extends Notifier<BookingState> {
  late final AppointmentRepository _repository;

  @override
  BookingState build() {
    _repository = ref.read(appointmentRepositoryProvider);
    return const BookingState();
  }

  Future<bool> book({
    required String medecinId,
    required DateTime dateTime,
    String? reason,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true, clearBooking: true);

    try {
      final appointment = await _repository.createAppointment(
        medecinId: medecinId,
        dateTime: dateTime,
        reason: reason,
      );
      // Invalidate the list so Home/Appointments screens refresh.
      ref.invalidate(appointmentListProvider);
      ref.invalidate(nextAppointmentProvider);
      state = BookingState(bookedAppointment: appointment);
      return true;
    } catch (error) {
      final message = _extractMessage(error);
      state = BookingState(errorMessage: message);
      return false;
    }
  }

  Future<bool> cancel(String appointmentId) async {
    state = state.copyWith(isLoading: true, clearError: true);

    try {
      await _repository.cancelAppointment(appointmentId);
      ref.invalidate(appointmentListProvider);
      ref.invalidate(nextAppointmentProvider);
      ref.invalidate(appointmentDetailProvider(appointmentId));
      state = const BookingState();
      return true;
    } catch (error) {
      state = BookingState(errorMessage: _extractMessage(error));
      return false;
    }
  }

  void reset() => state = const BookingState();

  String _extractMessage(Object error) {
    return 'Une erreur est survenue. Veuillez réessayer.';
  }
}
