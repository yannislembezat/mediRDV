import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../../data/models/user_model.dart';
import 'auth_provider.dart';

// ---------------------------------------------------------------------------
// Profile update state
// ---------------------------------------------------------------------------

class ProfileUpdateState {
  const ProfileUpdateState({
    this.isLoading = false,
    this.errorMessage,
    this.isSuccess = false,
  });

  final bool isLoading;
  final String? errorMessage;
  final bool isSuccess;

  ProfileUpdateState copyWith({
    bool? isLoading,
    String? errorMessage,
    bool? isSuccess,
    bool clearError = false,
  }) {
    return ProfileUpdateState(
      isLoading: isLoading ?? this.isLoading,
      errorMessage: clearError ? null : errorMessage ?? this.errorMessage,
      isSuccess: isSuccess ?? this.isSuccess,
    );
  }
}

final profileControllerProvider =
    NotifierProvider<ProfileController, ProfileUpdateState>(
      ProfileController.new,
    );

// ---------------------------------------------------------------------------
// Controller
// ---------------------------------------------------------------------------

class ProfileController extends Notifier<ProfileUpdateState> {
  late final Dio _dio;

  @override
  ProfileUpdateState build() {
    _dio = ref.read(dioProvider);
    return const ProfileUpdateState();
  }

  Future<bool> updateProfile({
    required String email,
    required String firstName,
    required String lastName,
    String? phone,
    String? dateOfBirth,
    String? gender,
    String? address,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true, isSuccess: false);

    try {
      final response = await _dio.put<Map<String, dynamic>>(
        ApiEndpoints.me,
        data: {
          'email': email.trim(),
          'firstName': firstName.trim(),
          'lastName': lastName.trim(),
          if (phone != null && phone.isNotEmpty) 'phone': phone.trim(),
          if (dateOfBirth != null && dateOfBirth.isNotEmpty)
            'dateOfBirth': dateOfBirth,
          if (gender != null && gender.isNotEmpty) 'gender': gender,
          if (address != null && address.isNotEmpty) 'address': address.trim(),
        },
      );

      final data = response.data?['data'] as Map<String, dynamic>?;
      if (data != null) {
        final updatedUser = UserModel.fromJson(data);
        // Propagate updated user into the auth state.
        ref.read(authControllerProvider.notifier).updateUser(updatedUser);
      }

      state = const ProfileUpdateState(isSuccess: true);
      return true;
    } on DioException catch (error) {
      final body = error.response?.data;
      String message = 'Mise à jour impossible. Réessayez plus tard.';
      if (body is Map<String, dynamic>) {
        final errors = body['errors'] as Map<String, dynamic>?;
        if (errors != null && errors.containsKey('email')) {
          message = (errors['email'] as List<dynamic>).first as String;
        } else {
          message = body['message'] as String? ?? message;
        }
      }
      state = ProfileUpdateState(errorMessage: message);
      return false;
    }
  }

  void reset() => state = const ProfileUpdateState();
}
