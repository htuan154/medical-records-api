import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'apiconfig.dart';
import 'token_service.dart';

class UserService {
  static const String _userKey = 'user_info';

  // ====== LOCAL STORAGE METHODS (giữ nguyên) ======
  static Future<void> saveUser(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user));
  }

  static Future<Map<String, dynamic>?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userString = prefs.getString(_userKey);
    if (userString == null) return null;
    return jsonDecode(userString);
  }

  static Future<void> clearUser() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_userKey);
  }

  // ====== API METHODS ======

  // Lấy danh sách tất cả user
  static Future<Map<String, dynamic>> list(Map<String, dynamic>? params) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      String query = '';
      if (params != null && params.isNotEmpty) {
        query = '?' + Uri(queryParameters: params).query;
      }
      final url = Uri.parse('${ApiConfig.baseUrl}/users$query');
      final response = await http
          .get(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể lấy danh sách user',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Lấy chi tiết user theo id
  static Future<Map<String, dynamic>> getUserById(String userId) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/users/$userId');
      final response = await http
          .get(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể lấy thông tin user',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Tạo user mới
  static Future<Map<String, dynamic>> create(
    Map<String, dynamic> userData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/users');
      final response = await http
          .post(
            url,
            headers: ApiConfig.getAuthHeaders(token),
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

  // Cập nhật thông tin user
  static Future<Map<String, dynamic>> update(
    String userId,
    Map<String, dynamic> userData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/users/$userId');
      final response = await http
          .put(
            url,
            headers: ApiConfig.getAuthHeaders(token),
            body: jsonEncode(userData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể cập nhật user',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Xóa user
  static Future<Map<String, dynamic>> remove(String userId) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/users/$userId');
      final response = await http
          .delete(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể xóa user',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Lấy danh sách available patients cho user
  static Future<Map<String, dynamic>> getAvailablePatients({
    int limit = 1000,
  }) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse(
        '${ApiConfig.baseUrl}/users/available-patients?limit=$limit',
      );
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
              responseData['message'] ??
              'Không thể lấy danh sách bệnh nhân chưa liên kết',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Lấy danh sách available doctors cho user
  static Future<Map<String, dynamic>> getAvailableDoctors({
    int limit = 1000,
  }) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse(
        '${ApiConfig.baseUrl}/users/available-doctors?limit=$limit',
      );
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
              responseData['message'] ??
              'Không thể lấy danh sách bác sĩ chưa liên kết',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Lấy danh sách available staffs cho user
  static Future<Map<String, dynamic>> getAvailableStaffs({
    int limit = 1000,
  }) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse(
        '${ApiConfig.baseUrl}/users/available-staffs?limit=$limit',
      );
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
              responseData['message'] ??
              'Không thể lấy danh sách nhân viên chưa liên kết',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }
}
