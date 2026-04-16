import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/user_model.dart';
import '../../data/repositories/auth_repository.dart';

enum AuthStatus { checking, authenticated, unauthenticated }

class AuthState {
  const AuthState({required this.status, this.user, this.isLoading = false});

  const AuthState.checking()
    : status = AuthStatus.checking,
      user = null,
      isLoading = true;

  const AuthState.unauthenticated()
    : status = AuthStatus.unauthenticated,
      user = null,
      isLoading = false;

  const AuthState.authenticated(this.user)
    : status = AuthStatus.authenticated,
      isLoading = false;

  final AuthStatus status;
  final UserModel? user;
  final bool isLoading;

  bool get isAuthenticated =>
      status == AuthStatus.authenticated && user != null;

  AuthState copyWith({
    AuthStatus? status,
    UserModel? user,
    bool? isLoading,
    bool clearUser = false,
  }) {
    return AuthState(
      status: status ?? this.status,
      user: clearUser ? null : user ?? this.user,
      isLoading: isLoading ?? this.isLoading,
    );
  }
}

final authControllerProvider = NotifierProvider<AuthController, AuthState>(
  AuthController.new,
);

final currentUserProvider = Provider<UserModel?>((ref) {
  return ref.watch(authControllerProvider).user;
});

class AuthController extends Notifier<AuthState> {
  late final AuthRepository _authRepository;

  @override
  AuthState build() {
    _authRepository = ref.read(authRepositoryProvider);
    Future<void>.microtask(_restoreSession);
    return const AuthState.checking();
  }

  Future<void> _restoreSession() async {
    final snapshot = await _authRepository.restoreSession();
    if (!snapshot.isAuthenticated) {
      state = const AuthState.unauthenticated();
      return;
    }

    // Fetch full user profile from the API using the restored token.
    final user = await _authRepository.fetchCurrentUser(snapshot.accessToken!);
    if (user != null) {
      state = AuthState.authenticated(user);
    } else {
      // Token exists but profile fetch failed — fall back to a session stub.
      state = AuthState.authenticated(_stubUser(snapshot.email));
    }
  }

  Future<String?> signIn({
    required String email,
    required String password,
  }) async {
    state = state.copyWith(isLoading: true);

    try {
      final snapshot = await _authRepository.signIn(
        email: email,
        password: password,
      );
      final user = await _authRepository.fetchCurrentUser(snapshot.accessToken!);
      state = AuthState.authenticated(user ?? _stubUser(snapshot.email));
      return null;
    } on DioException catch (error) {
      state = const AuthState.unauthenticated();
      return error.response?.data is Map<String, dynamic>
          ? (error.response?.data['message'] as String? ??
                'Connexion impossible pour le moment.')
          : 'Connexion impossible pour le moment.';
    }
  }

  /// Registers a new patient, then signs them in automatically.
  Future<String?> signUp({
    required String email,
    required String password,
    required String firstName,
    required String lastName,
    String? phone,
  }) async {
    state = state.copyWith(isLoading: true);

    try {
      await _authRepository.signUp(
        email: email,
        password: password,
        firstName: firstName,
        lastName: lastName,
        phone: phone,
      );
      // Auto sign-in after successful registration.
      return signIn(email: email, password: password);
    } on DioException catch (error) {
      state = const AuthState.unauthenticated();
      final body = error.response?.data;
      if (body is Map<String, dynamic>) {
        final errors = body['errors'] as Map<String, dynamic>?;
        if (errors != null && errors.containsKey('email')) {
          return (errors['email'] as List<dynamic>).first as String;
        }
        return body['message'] as String? ?? 'Inscription impossible pour le moment.';
      }
      return 'Inscription impossible pour le moment.';
    }
  }

  Future<void> signInDemo() async {
    state = state.copyWith(isLoading: true);
    await _authRepository.persistTokens(
      accessToken: 'demo-access-token',
      refreshToken: 'demo-refresh-token',
      email: 'patient@medirdv.fr',
    );
    state = AuthState.authenticated(_stubUser('patient@medirdv.fr'));
  }

  Future<void> signOut() async {
    state = state.copyWith(isLoading: true);
    await _authRepository.clearTokens();
    state = const AuthState.unauthenticated();
  }

  void handleSessionExpired() {
    state = const AuthState.unauthenticated();
  }

  void updateUser(UserModel updatedUser) {
    if (state.isAuthenticated) {
      state = AuthState.authenticated(updatedUser);
    }
  }

  UserModel _stubUser(String? email) {
    return UserModel(
      id: 'patient-session',
      fullName: email?.split('@').first ?? 'Patient',
      email: email ?? 'patient@medirdv.fr',
      role: 'ROLE_PATIENT',
    );
  }
}
