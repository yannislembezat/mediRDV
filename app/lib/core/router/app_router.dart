import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../presentation/providers/auth_provider.dart';
import '../../presentation/screens/appointment/appointment_detail_screen.dart';
import '../../presentation/screens/appointment/appointments_list_screen.dart';
import '../../presentation/screens/appointment/booking_screen.dart';
import '../../presentation/screens/auth/login_screen.dart';
import '../../presentation/screens/auth/register_screen.dart';
import '../../presentation/screens/doctor/doctor_detail_screen.dart';
import '../../presentation/screens/home/home_screen.dart';
import '../../presentation/screens/medical/consultation_detail_screen.dart';
import '../../presentation/screens/medical/medical_records_screen.dart';
import '../../presentation/screens/notification/notification_screen.dart';
import '../../presentation/screens/onboarding/onboarding_screen.dart';
import '../../presentation/screens/profile/edit_profile_screen.dart';
import '../../presentation/screens/profile/profile_screen.dart';
import '../../presentation/screens/search/search_screen.dart';
import '../../presentation/screens/splash/splash_screen.dart';

abstract final class AppRoutes {
  static const splash = '/';
  static const onboarding = '/onboarding';
  static const login = '/login';
  static const register = '/register';
  static const home = '/home';
  static const search = '/search';
  static const appointments = '/appointments';
  static const medicalRecords = '/medical-records';
  static const profile = '/profile';
  static const editProfile = '/profile/edit';
  static const notifications = '/notifications';

  static String doctorDetail(String medecinId) => '/doctor/$medecinId';
  static String bookDoctor(String medecinId) => '/doctor/$medecinId/book';
  static String appointmentDetail(String appointmentId) =>
      '/appointments/$appointmentId';
  static String consultationDetail(String consultationId) =>
      '/consultations/$consultationId';
}

final appRouterProvider = Provider<GoRouter>((ref) {
  final refreshListenable = ValueNotifier<int>(0);
  ref
    ..onDispose(refreshListenable.dispose)
    ..listen(authControllerProvider, (previous, next) {
      refreshListenable.value++;
    });

  return GoRouter(
    initialLocation: AppRoutes.splash,
    refreshListenable: refreshListenable,
    redirect: (context, state) {
      final authState = ref.read(authControllerProvider);
      final location = state.matchedLocation;
      final isPublicRoute = {
        AppRoutes.splash,
        AppRoutes.onboarding,
        AppRoutes.login,
        AppRoutes.register,
      }.contains(location);

      if (authState.status == AuthStatus.checking) {
        return location == AppRoutes.splash ? null : AppRoutes.splash;
      }

      if (!authState.isAuthenticated) {
        if (location == AppRoutes.splash) {
          return AppRoutes.onboarding;
        }

        return isPublicRoute ? null : AppRoutes.login;
      }

      if (isPublicRoute) {
        return AppRoutes.home;
      }

      return null;
    },
    routes: [
      GoRoute(
        path: AppRoutes.splash,
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: AppRoutes.onboarding,
        builder: (context, state) => const OnboardingScreen(),
      ),
      GoRoute(
        path: AppRoutes.login,
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: AppRoutes.register,
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: AppRoutes.home,
        builder: (context, state) => const HomeScreen(),
      ),
      GoRoute(
        path: AppRoutes.search,
        builder: (context, state) => const SearchScreen(),
      ),
      GoRoute(
        path: '/doctor/:id',
        builder: (context, state) =>
            DoctorDetailScreen(medecinId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: '/doctor/:id/book',
        builder: (context, state) =>
            BookingScreen(medecinId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: AppRoutes.appointments,
        builder: (context, state) => const AppointmentsListScreen(),
      ),
      GoRoute(
        path: '/appointments/:id',
        builder: (context, state) =>
            AppointmentDetailScreen(appointmentId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: AppRoutes.medicalRecords,
        builder: (context, state) => const MedicalRecordsScreen(),
      ),
      GoRoute(
        path: '/consultations/:id',
        builder: (context, state) => ConsultationDetailScreen(
          consultationId: state.pathParameters['id']!,
        ),
      ),
      GoRoute(
        path: AppRoutes.profile,
        builder: (context, state) => const ProfileScreen(),
      ),
      GoRoute(
        path: AppRoutes.editProfile,
        builder: (context, state) => const EditProfileScreen(),
      ),
      GoRoute(
        path: AppRoutes.notifications,
        builder: (context, state) => const NotificationScreen(),
      ),
    ],
  );
});
