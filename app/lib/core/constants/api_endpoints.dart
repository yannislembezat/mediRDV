abstract final class ApiEndpoints {
  static const appName = String.fromEnvironment(
    'APP_NAME',
    defaultValue: 'MediRDV',
  );
  static const appEnv = String.fromEnvironment(
    'APP_ENV',
    defaultValue: 'local',
  );
  static const baseUrl = 'http://10.0.2.2:8000/api';
  static const connectTimeoutMs = int.fromEnvironment(
    'CONNECT_TIMEOUT_MS',
    defaultValue: 10000,
  );
  static const receiveTimeoutMs = int.fromEnvironment(
    'RECEIVE_TIMEOUT_MS',
    defaultValue: 10000,
  );

  // Auth
  static const login = '/login';
  static const register = '/register';
  static const refreshToken = '/token/refresh';

  // Profile
  static const me = '/me';

  // Catalog
  static const specialties = '/specialties';
  static const medecins = '/medecins';

  // Appointments
  static const appointments = '/appointments';

  // Medical records
  static const medicalRecords = '/medical-records';

  // Notifications
  static const notifications = '/notifications';

  // Path helpers
  static String medecinDetail(String id) => '$medecins/$id';
  static String medecinSlots(String id) => '$medecins/$id/slots';
  static String appointmentDetail(String id) => '$appointments/$id';
  static String medicalRecordDetail(String id) => '$medicalRecords/$id';
  static String notificationMarkRead(String id) => '$notifications/$id/read';
}
