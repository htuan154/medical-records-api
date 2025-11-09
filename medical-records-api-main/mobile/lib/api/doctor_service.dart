import 'dart:convert';
import 'package:http/http.dart' as http;
import 'apiconfig.dart';
import 'token_service.dart';

class DoctorService {
  // Lấy danh sách tất cả bác sĩ
  static Future<Map<String, dynamic>> getDoctors() async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/doctors');
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
              responseData['message'] ?? 'Không thể lấy danh sách bác sĩ',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Lấy chi tiết bác sĩ theo id
  static Future<Map<String, dynamic>> getDoctorById(String doctorId) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/doctors/$doctorId');
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
              responseData['message'] ?? 'Không thể lấy thông tin bác sĩ',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Tạo bác sĩ mới
  static Future<Map<String, dynamic>> createDoctor(
    Map<String, dynamic> doctorData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/doctors');
      final response = await http
          .post(
            url,
            headers: ApiConfig.getAuthHeaders(token),
            body: jsonEncode(doctorData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 201) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể tạo bác sĩ',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Cập nhật thông tin bác sĩ
  static Future<Map<String, dynamic>> updateDoctor(
    String doctorId,
    Map<String, dynamic> doctorData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/doctors/$doctorId');
      final response = await http
          .put(
            url,
            headers: ApiConfig.getAuthHeaders(token),
            body: jsonEncode(doctorData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể cập nhật bác sĩ',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Xóa bác sĩ
  static Future<Map<String, dynamic>> deleteDoctor(String doctorId) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/doctors/$doctorId');
      final response = await http
          .delete(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể xóa bác sĩ',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }
}
