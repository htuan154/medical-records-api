import 'dart:convert';
import 'package:http/http.dart' as http;
import 'apiconfig.dart';
import 'token_service.dart';

class InvoiceService {
  // Lấy danh sách tất cả hóa đơn (có thể truyền params để filter)
  static Future<Map<String, dynamic>> getInvoices({
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
      final url = Uri.parse('${ApiConfig.baseUrl}/invoices$query');
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
              responseData['message'] ?? 'Không thể lấy danh sách hóa đơn',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Lấy chi tiết hóa đơn theo id
  static Future<Map<String, dynamic>> getInvoiceById(String invoiceId) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/invoices/$invoiceId');
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
              responseData['message'] ?? 'Không thể lấy thông tin hóa đơn',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Tạo hóa đơn mới
  static Future<Map<String, dynamic>> createInvoice(
    Map<String, dynamic> invoiceData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/invoices');
      final response = await http
          .post(
            url,
            headers: ApiConfig.getAuthHeaders(token),
            body: jsonEncode(invoiceData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 201) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể tạo hóa đơn',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Cập nhật thông tin hóa đơn
  static Future<Map<String, dynamic>> updateInvoice(
    String invoiceId,
    Map<String, dynamic> invoiceData,
  ) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/invoices/$invoiceId');
      final response = await http
          .put(
            url,
            headers: ApiConfig.getAuthHeaders(token),
            body: jsonEncode(invoiceData),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể cập nhật hóa đơn',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }

  // Xóa hóa đơn
  static Future<Map<String, dynamic>> deleteInvoice(String invoiceId) async {
    final token = await TokenService.getToken();
    if (token == null || token.isEmpty) {
      return {'success': false, 'message': 'Chưa đăng nhập'};
    }
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}/invoices/$invoiceId');
      final response = await http
          .delete(url, headers: ApiConfig.getAuthHeaders(token))
          .timeout(Duration(seconds: ApiConfig.timeoutDuration));
      final responseData = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': responseData};
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Không thể xóa hóa đơn',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Lỗi kết nối: $e'};
    }
  }
}
