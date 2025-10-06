import 'dart:convert';
import 'package:http/http.dart' as http;
import 'apiconfig.dart';
import 'token_service.dart';

class StaffService {
  // Lấy danh sách tất cả nhân viên
  static Future<Map<String, dynamic>> getStaffs() async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/staffs');
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
              responseData['message'] ?? 'Không thể lấy danh sách nhân viên',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Lấy chi tiết nhân viên theo id
  static Future<Map<String, dynamic>> getStaffById(String staffId) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/staffs/$staffId');
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
              responseData['message'] ?? 'Không thể lấy thông tin nhân viên',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Tạo nhân viên mới
  static Future<Map<String, dynamic>> createStaff(
    Map<String, dynamic> staffData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/staffs');
      final response = await http
          .post(
            url,
            headers: ApiConfig.getAuthHeaders(token),
            body: jsonEncode(staffData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 201) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể tạo nhân viên',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Cập nhật thông tin nhân viên
  static Future<Map<String, dynamic>> updateStaff(
    String staffId,
    Map<String, dynamic> staffData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/staffs/$staffId');
      final response = await http
          .put(
            url,
            headers: ApiConfig.getAuthHeaders(token),
            body: jsonEncode(staffData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể cập nhật nhân viên',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Xóa nhân viên
  static Future<Map<String, dynamic>> deleteStaff(String staffId) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/staffs/$staffId');
      final response = await http
          .delete(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể xóa nhân viên',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }
}
