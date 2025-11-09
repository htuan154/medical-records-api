import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'apiconfig.dart';

class AuthService {
  // Key để lưu token trong SharedPreferences
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'user_data';

  // Lưu token vào SharedPreferences
  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  // Lấy token từ SharedPreferences
  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  // Lưu thông tin user
  static Future<void> saveUser(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user));
  }

  // Lấy thông tin user
  static Future<Map<String, dynamic>?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userString = prefs.getString(_userKey);
    if (userString != null) {
      return jsonDecode(userString);
    }
    return null;
  }

  // Xóa token và user data (đăng xuất)
  static Future<void> clearAuthData() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
  }

  // Kiểm tra xem đã đăng nhập chưa
  static Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  // Login API
  static Future<Map<String, dynamic>> login({
    required String username,
    required String password,
  }) async {
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/login');
      print('🚀 Đang gửi request tới: $url');
      print('📝 Username: $username');

      final response = await http
          .post(
            url,
            headers: ApiConfig.defaultHeaders,
            body: jsonEncode({'username': username, 'password': password}),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));

      print('📡 Response status: ${response.statusCode}');
      print('📋 Response body: ${response.body}');

      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200) {
        // Đăng nhập thành công - lấy đúng trường access_token
        final token = responseData['access_token'] ?? '';
        final refreshToken = responseData['refresh_token'] ?? '';
        final user = responseData['user'] ?? {};

        print('✅ Đăng nhập thành công');
        final shortToken = token.length > 20 ? token.substring(0, 20) : token;
        print('🔑 Token: $shortToken...');

        if (token.isNotEmpty) {
          await saveToken(token);
          await saveUser(user);
        }

        // Trả về đúng cấu trúc cho màn hình đăng nhập
        return {
          'ok': responseData['ok'] ?? true,
          'access_token': token,
          'refresh_token': refreshToken,
          'user': user,
        };
      } else {
        // Đăng nhập thất bại
        print('❌ Đăng nhập thất bại: ${response.statusCode}');
        return {
          'ok': false,
          'message': responseData['message'] ?? 'Đăng nhập thất bại',
          'errors': responseData['errors'] ?? {},
        };
      }
    } catch (e) {
      // Lỗi kết nối hoặc timeout
      print('💥 Lỗi khi đăng nhập: $e');
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Get user profile API
  static Future<Map<String, dynamic>> getProfile(String token) async {
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/me');

      final response = await http
          .get(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));

      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message':
              responseData['message'] ?? 'Không thể lấy thông tin người dùng',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Logout API
  static Future<Map<String, dynamic>> logout([String? token]) async {
    try {
      // Nếu không truyền token, lấy từ storage
      token ??= await getToken();

      if (token == null || token.isEmpty) {
        await clearAuthData();
        return {'success': true, 'message': 'Đăng xuất thành công'};
      }

      final url = Uri.parse('${ApiConfig.baseUrl}/logout');

      final response = await http
          .post(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));

      // Xóa dữ liệu auth dù API thành công hay thất bại
      await clearAuthData();

      if (response.statusCode == 200) {
        return {'success': true, 'message': 'Đăng xuất thành công'};
      } else {
        return {
          'success': true, // Vẫn return success vì đã xóa local data
          'message': 'Đăng xuất thành công',
        };
      }
    } catch (e) {
      // Vẫn xóa dữ liệu local khi có lỗi
      await clearAuthData();
      return {'success': true, 'message': 'Đăng xuất thành công'};
    }
  }

  // Refresh token API
  static Future<Map<String, dynamic>> refreshToken(String token) async {
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/refresh');

      final response = await http
          .post(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));

      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200) {
        // Lưu token mới nếu có
        final newToken = responseData['token'] ?? '';
        if (newToken.isNotEmpty) {
          await saveToken(newToken);
        }

        return {'success': true, 'data': responseData, 'token': newToken};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể làm mới token',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Tạo user mới
  static Future<Map<String, dynamic>> createUser(
    Map<String, dynamic> userData,
  ) async {
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/users');
      final response = await http
          .post(
            url,
            headers: ApiConfig.defaultHeaders,
            body: jsonEncode(userData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));

      final responseData = jsonDecode(response.body);

      if (response.statusCode == 201) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể tạo user',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Hash password (simple hash - thực tế nên dùng bcrypt)
  static String hashPassword(String password) {
    // Sử dụng simple hash, server sẽ hash lại với bcrypt
    return password; // Tạm thời gửi plain text, server sẽ hash
  }

  // Tạo ID với timestamp
  static String generateUserId() {
    final now = DateTime.now();
    return 'user_patient_${now.second}${now.minute}${now.hour}${now.day}${now.month}${now.year}';
  }

  static String generatePatientId() {
    final now = DateTime.now();
    return 'patient_${now.second}${now.minute}${now.hour}${now.day}${now.month}${now.year}';
  }
}
