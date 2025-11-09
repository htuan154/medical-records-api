class ApiConfig {
  // Base URL của server Laravel
  // Sử dụng ngrok URL để demo từ xa (thay đổi URL này theo ngrok tunnel của bạn)
  static const String baseUrl = 'https://medical-records-api-izzn.onrender.com/api/v1';

  // Timeout cho các request (tăng lên 60 giây)
  static const int timeoutDuration = 60; // seconds

  // Headers mặc định
  static Map<String, String> get defaultHeaders => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  // Headers với JWT token
  static Map<String, String> getAuthHeaders(String token) => {
    ...defaultHeaders,
    'Authorization': 'Bearer $token',
  };
}
