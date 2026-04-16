import 'package:dio/dio.dart';
import 'package:flutter/widgets.dart';

import '../../data/repositories/auth_repository.dart';
import '../constants/api_endpoints.dart';

class JwtInterceptor extends QueuedInterceptor {
  JwtInterceptor({
    required this.dio,
    required this.authRepository,
    required this.onUnauthorized,
  });

  final Dio dio;
  final AuthRepository authRepository;
  final VoidCallback onUnauthorized;

  Future<bool>? _refreshFuture;

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await authRepository.getAccessToken();
    if (token != null && token.isNotEmpty) {
      options.headers['Authorization'] = 'Bearer $token';
    }

    handler.next(options);
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    final statusCode = err.response?.statusCode;
    final alreadyRetried = err.requestOptions.extra['retried'] == true;
    final isRefreshCall = err.requestOptions.path.contains(
      ApiEndpoints.refreshToken,
    );

    if (statusCode != 401 || alreadyRetried || isRefreshCall) {
      handler.next(err);
      return;
    }

    _refreshFuture ??= authRepository.refreshToken();
    final refreshed = await _refreshFuture!;
    _refreshFuture = null;

    if (!refreshed) {
      await authRepository.clearTokens();
      onUnauthorized();
      handler.next(err);
      return;
    }

    final token = await authRepository.getAccessToken();
    final requestOptions = err.requestOptions;
    requestOptions.headers['Authorization'] = 'Bearer $token';
    requestOptions.extra['retried'] = true;

    final response = await dio.fetch<dynamic>(requestOptions);
    handler.resolve(response);
  }
}
