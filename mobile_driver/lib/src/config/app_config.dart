class AppConfig {
  const AppConfig._();

  static const apiOrigin = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://doz.itca-kh.com',
  );

  static const driverApi = '$apiOrigin/api/v1/driver';
  static const publicApi = '$apiOrigin/api/v1';
}
