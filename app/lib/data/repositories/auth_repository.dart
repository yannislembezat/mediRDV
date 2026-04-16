import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../../core/constants/api_endpoints.dart';
import '../models/user_model.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return const AuthRepository();
});

class AuthSessionSnapshot {
  const AuthSessionSnapshot({this.accessToken, this.refreshToken, this.email});

  final String? accessToken;
  final String? refreshToken;
  final String? email;

  bool get isAuthenticated => accessToken != null && accessToken!.isNotEmpty;
}

class AuthRepository {
  const AuthRepository();

  static const _accessTokenKey = 'access_token';
  static const _refreshTokenKey = 'refresh_token';
  static const _userEmailKey = 'user_email';

  FlutterSecureStorage get _storage => const FlutterSecureStorage();

  Dio _publicClient() {
    return Dio(
      BaseOptions(
        baseUrl: ApiEndpoints.baseUrl,
        connectTimeout: const Duration(milliseconds: ApiEndpoints.connectTimeoutMs),
        receiveTimeout: const Duration(milliseconds: ApiEndpoints.receiveTimeoutMs),
        headers: const {'Content-Type': 'application/json'},
      ),
    );
  }

  Future<AuthSessionSnapshot> restoreSession() async {
    final accessToken = await getAccessToken();
    final storedRefreshToken = await getRefreshToken();
    final email = await _storage.read(key: _userEmailKey);

    if (accessToken != null && accessToken.isNotEmpty) {
      return AuthSessionSnapshot(
        accessToken: accessToken,
        refreshToken: storedRefreshToken,
        email: email,
      );
    }

    if (storedRefreshToken != null && storedRefreshToken.isNotEmpty) {
      final refreshed = await refreshToken();
      if (refreshed) {
        return AuthSessionSnapshot(
          accessToken: await getAccessToken(),
          refreshToken: await getRefreshToken(),
          email: email,
        );
      }
    }

    return const AuthSessionSnapshot();
  }

  Future<AuthSessionSnapshot> signIn({
    required String email,
    required String password,
  }) async {
    final response = await _publicClient().post<Map<String, dynamic>>(
      ApiEndpoints.login,
      data: {'email': email.trim(), 'password': password},
    );

    final payload = response.data ?? const <String, dynamic>{};
    final accessToken = payload['token'] as String?;
    final storedRefreshToken = payload['refreshToken'] as String?;

    await persistTokens(
      accessToken: accessToken ?? '',
      refreshToken: storedRefreshToken ?? '',
      email: email.trim(),
    );

    return AuthSessionSnapshot(
      accessToken: await getAccessToken(),
      refreshToken: await getRefreshToken(),
      email: email.trim(),
    );
  }

  /// Registers a new patient account. Returns the created [UserModel].
  Future<UserModel> signUp({
    required String email,
    required String password,
    required String firstName,
    required String lastName,
    String? phone,
    String? dateOfBirth,
    String? gender,
    String? address,
  }) async {
    final response = await _publicClient().post<Map<String, dynamic>>(
      ApiEndpoints.register,
      data: {
        'email': email.trim(),
        'password': password,
        'firstName': firstName.trim(),
        'lastName': lastName.trim(),
        if (phone != null && phone.isNotEmpty) 'phone': phone.trim(),
        if (dateOfBirth != null && dateOfBirth.isNotEmpty) 'dateOfBirth': dateOfBirth,
        if (gender != null && gender.isNotEmpty) 'gender': gender,
        if (address != null && address.isNotEmpty) 'address': address.trim(),
      },
    );

    final data = response.data?['data'] as Map<String, dynamic>?;
    if (data == null) {
      throw StateError('Réponse invalide du serveur lors de l\'inscription.');
    }
    return UserModel.fromJson(data);
  }

  /// Fetches the authenticated user profile from `/api/me`.
  Future<UserModel?> fetchCurrentUser(String accessToken) async {
    try {
      final client = _publicClient();
      client.options.headers['Authorization'] = 'Bearer $accessToken';
      final response = await client.get<Map<String, dynamic>>(ApiEndpoints.me);
      final data = response.data?['data'] as Map<String, dynamic>?;
      return data != null ? UserModel.fromJson(data) : null;
    } on DioException {
      return null;
    }
  }

  Future<void> persistTokens({
    required String accessToken,
    required String refreshToken,
    String? email,
  }) async {
    await _storage.write(key: _accessTokenKey, value: accessToken);
    await _storage.write(key: _refreshTokenKey, value: refreshToken);
    if (email != null && email.isNotEmpty) {
      await _storage.write(key: _userEmailKey, value: email);
    }
  }

  Future<String?> getAccessToken() {
    return _storage.read(key: _accessTokenKey);
  }

  Future<String?> getRefreshToken() {
    return _storage.read(key: _refreshTokenKey);
  }

  Future<bool> refreshToken() async {
    final storedRefreshToken = await getRefreshToken();
    if (storedRefreshToken == null || storedRefreshToken.isEmpty) {
      return false;
    }

    try {
      final response = await _publicClient().post<Map<String, dynamic>>(
        ApiEndpoints.refreshToken,
        data: {'refreshToken': storedRefreshToken},
      );
      final payload = response.data ?? const <String, dynamic>{};
      final nextAccessToken = payload['token'] as String?;
      final nextRefreshToken = payload['refreshToken'] as String? ?? storedRefreshToken;

      if (nextAccessToken == null || nextAccessToken.isEmpty) {
        return false;
      }

      await persistTokens(
        accessToken: nextAccessToken,
        refreshToken: nextRefreshToken,
        email: await _storage.read(key: _userEmailKey),
      );

      return true;
    } on DioException {
      return false;
    }
  }

  /// Clears all stored tokens and session data. Called on logout or 401 after refresh failure.
  Future<void> clearTokens() async {
    await _storage.delete(key: _accessTokenKey);
    await _storage.delete(key: _refreshTokenKey);
    await _storage.delete(key: _userEmailKey);
  }
}
