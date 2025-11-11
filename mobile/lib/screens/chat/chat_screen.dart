
import 'package:flutter/material.dart';
import '../../api/consultation_service.dart';
import '../../api/user_service.dart';
import '../../api/patient_service.dart';
import 'chat_detail_screen.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({Key? key}) : super(key: key);

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  Future<void> _createConsultation() async {
    // Get user info
    final user = await UserService.getUser();
    if (user == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Không tìm thấy thông tin người dùng'), backgroundColor: Colors.red),
      );
      return;
    }
    final patientId = user['linked_patient_id'];
    // Lấy thông tin bệnh nhân từ PatientService
    String patientName = '';
    String patientPhone = '';
    try {
      final patientRes = await PatientService.getPatientById(patientId);
      if (patientRes['success'] == true && patientRes['data'] != null) {
        final patientData = patientRes['data'];
        final personalInfo = patientData['personal_info'] ?? {};
        patientName = personalInfo['full_name'] ?? '';
        patientPhone = personalInfo['phone'] ?? '';
      }
    } catch (e) {
      // Nếu lỗi vẫn để rỗng, backend sẽ báo lỗi
    }
    final patientInfo = {
      'name': patientName,
      'phone': patientPhone,
      'avatar': null,
    };
  // Generate _id: consultation_giâyphútgiờngàythángnăm (HHmmss_ddMMyyyy)
  final now = DateTime.now();
  final id = 'consultation_' +
    now.second.toString().padLeft(2, '0') +
    now.minute.toString().padLeft(2, '0') +
    now.hour.toString().padLeft(2, '0') +
    now.day.toString().padLeft(2, '0') +
    now.month.toString().padLeft(2, '0') +
    now.year.toString().padLeft(4, '0');
    final createdAt = now.toUtc().toIso8601String();
    final consultationData = {
      '_id': id,
      'type': 'consultation',
      'patient_id': patientId,
      'patient_info': patientInfo,
      'staff_id': null,
      'staff_info': {
        'name': null,
        'staff_type': null,
        'avatar': null,
      },
      'status': 'active',
      'last_message': null,
      'last_message_at': null,
      'unread_count_patient': 0,
      'unread_count_staff': 0,
      'created_at': createdAt,
      'updated_at': createdAt,
    };
    print('DEBUG: consultationData gửi lên:');
    print(consultationData);
    final result = await ConsultationService.createConsultation(consultationData);
    print('DEBUG: Kết quả trả về từ API:');
    print(result);
    if (result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Tạo phiên tư vấn thành công'), backgroundColor: Colors.green),
      );
      await _loadConsultations();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Không thể tạo phiên tư vấn'), backgroundColor: Colors.red),
      );
    }
  }
  List<dynamic> consultations = [];
  bool isLoading = true;
  String? errorMessage;
  String? patientId;

  @override
  void initState() {
    super.initState();
    _loadConsultations();
  }

  Future<void> _loadConsultations() async {
    setState(() {
      isLoading = true;
      errorMessage = null;
    });
    try {
      final user = await UserService.getUser();
      if (user == null) {
        setState(() {
          errorMessage = 'Không tìm thấy thông tin người dùng';
          isLoading = false;
        });
        return;
      }
      patientId = user['linked_patient_id'];
      if (patientId == null || patientId!.isEmpty) {
        setState(() {
          errorMessage = 'Không tìm thấy ID bệnh nhân';
          isLoading = false;
        });
        return;
      }
      final result = await ConsultationService.getConsultations(params: {'patient_id': patientId});
      if (result['success'] != true) {
        setState(() {
          errorMessage = result['message'] ?? 'Không thể lấy danh sách phiên tư vấn';
          isLoading = false;
        });
        return;
      }
      final rows = result['data']['rows'] as List<dynamic>?;
      final filtered = rows?.map((row) => row['doc']).toList() ?? [];
      setState(() {
        consultations = filtered;
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        errorMessage = 'Lỗi kết nối: $e';
        isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Lịch sử trò chuyện'),
        backgroundColor: Colors.blue.shade600,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : errorMessage != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
                      const SizedBox(height: 16),
                      Text(errorMessage!, style: const TextStyle(fontSize: 16)),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadConsultations,
                        child: const Text('Thử lại'),
                      ),
                    ],
                  ),
                )
              : consultations.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.chat_bubble_outline, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text(
                            'Chưa có phiên tư vấn nào',
                            style: TextStyle(fontSize: 16, color: Colors.grey),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadConsultations,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: consultations.length,
                        itemBuilder: (context, index) {
                          final consultation = consultations[index];
                          return ConsultationCard(consultation: consultation);
                        },
                      ),
                    ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 70.0, right: 0),
        child: FloatingActionButton(
          onPressed: _createConsultation,
          child: const Icon(Icons.add),
          backgroundColor: Colors.blue,
          tooltip: 'Tạo phiên tư vấn mới',
        ),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.endDocked,
    );
  }
}

class ConsultationCard extends StatelessWidget {
  final Map<String, dynamic> consultation;
  const ConsultationCard({Key? key, required this.consultation}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final staffInfo = consultation['staff_info'] ?? {};
    final staffName = staffInfo['name'] ?? 'Chưa có nhân viên';
    final staffType = staffInfo['staff_type'] ?? '';
    final status = consultation['status'] ?? '';

    Color statusColor;
    String statusText;
    switch (status) {
      case 'active':
        statusColor = Colors.green;
        statusText = 'Đang hoạt động';
        break;
      case 'waiting':
        statusColor = Colors.orange;
        statusText = 'Đang chờ';
        break;
      case 'closed':
        statusColor = Colors.grey;
        statusText = 'Đã đóng';
        break;
      default:
        statusColor = Colors.grey;
        statusText = status;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => ChatDetailScreen(consultation: consultation),
            ),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    staffName,
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    staffType,
                    style: const TextStyle(fontSize: 14, color: Colors.black54),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.15),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  statusText,
                  style: TextStyle(
                    color: statusColor,
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
