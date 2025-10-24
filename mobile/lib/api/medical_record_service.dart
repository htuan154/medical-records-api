import 'dart:convert';
import 'package:http/http.dart' as http;
import 'apiconfig.dart';
import 'token_service.dart';

class MedicalRecordService {
  // Lấy danh sách tất cả hồ sơ bệnh án (có thể truyền params để filter)
  static Future<Map<String, dynamic>> getMedicalRecords({
    Map<String, dynamic>? params,
  }) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      String query = '';
      if (params != null && params.isNotEmpty) {
        query =
            '?' +
            params.entries
                .map(
                  (e) => '${e.key}=${Uri.encodeComponent(e.value.toString())}',
                )
                .join('&');
      }
      final url = Uri.parse('${ApiConfig.baseUrl}/medical-records$query');
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
              'Không thể lấy danh sách hồ sơ bệnh án',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Lấy chi tiết hồ sơ bệnh án theo id
  static Future<Map<String, dynamic>> getMedicalRecordById(
    String recordId,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/medical-records/$recordId');
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
              'Không thể lấy thông tin hồ sơ bệnh án',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Tạo hồ sơ bệnh án mới
  static Future<Map<String, dynamic>> createMedicalRecord(
    Map<String, dynamic> recordData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/medical-records');
      final response = await http
          .post(
            url,
            headers: ApiConfig.getAuthHeaders(token),
            body: jsonEncode(recordData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 201) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể tạo hồ sơ bệnh án',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Cập nhật thông tin hồ sơ bệnh án
  static Future<Map<String, dynamic>> updateMedicalRecord(
    String recordId,
    Map<String, dynamic> recordData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/medical-records/$recordId');
      final response = await http
          .put(
            url,
            headers: ApiConfig.getAuthHeaders(token),
            body: jsonEncode(recordData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message':
              responseData['message'] ?? 'Không thể cập nhật hồ sơ bệnh án',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Xóa hồ sơ bệnh án
  static Future<Map<String, dynamic>> deleteMedicalRecord(
    String recordId,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/medical-records/$recordId');
      final response = await http
          .delete(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể xóa hồ sơ bệnh án',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }
}
