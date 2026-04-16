import 'package:dio/dio.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/repositories/auth_repository.dart';
import '../../presentation/providers/auth_provider.dart';
import '../constants/api_endpoints.dart';
import 'jwt_interceptor.dart';

final dioProvider = Provider<Dio>((ref) {
  final authRepository = ref.read(authRepositoryProvider);
  final authController = ref.read(authControllerProvider.notifier);

  return DioClient(
    authRepository: authRepository,
    onUnauthorized: authController.handleSessionExpired,
  ).dio;
});

class DioClient {
  DioClient({
    required AuthRepository authRepository,
    required VoidCallback onUnauthorized,
  }) : _dio = Dio(
         BaseOptions(
           baseUrl: ApiEndpoints.baseUrl,
           connectTimeout: const Duration(
             milliseconds: ApiEndpoints.connectTimeoutMs,
           ),
           receiveTimeout: const Duration(
             milliseconds: ApiEndpoints.receiveTimeoutMs,
           ),
           headers: const {'Content-Type': 'application/json'},
         ),
       ) {
    _dio.interceptors.add(
      JwtInterceptor(
        dio: _dio,
        authRepository: authRepository,
        onUnauthorized: onUnauthorized,
      ),
    );
  }

  final Dio _dio;

  Dio get dio => _dio;
}
