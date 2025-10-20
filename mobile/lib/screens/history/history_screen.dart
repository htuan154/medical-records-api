import 'package:flutter/material.dart';
import '../../api/appointment_service.dart';
import '../../api/user_service.dart';
import '../../api/doctor_service.dart';
import 'appointment_detail_screen.dart';

class HistoryScreenContent extends StatefulWidget {
  @override
  State<HistoryScreenContent> createState() => _HistoryScreenContentState();
}

class _HistoryScreenContentState extends State<HistoryScreenContent> {
  List<dynamic> appointments = [];
  List<dynamic> doctors = [];
  bool isLoading = true;
  String? errorMessage;
  String? patientId;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      isLoading = true;
      errorMessage = null;
    });

    try {
      // Lấy thông tin user để có linked_patient_id
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

      // Load danh sách bác sĩ
      final doctorsResult = await DoctorService.getDoctors();
      // Debug: In ra patientId
      print('[DEBUG] patientId: $patientId');
      // Debug: In ra response server trả về cho danh sách bác sĩ
      print(
        '[DEBUG] doctorsResult (full response): ${doctorsResult.toString()}',
      );
      if (doctorsResult['success'] != true) {
        setState(() {
          errorMessage =
              doctorsResult['message'] ?? 'Không thể lấy danh sách bác sĩ';
          isLoading = false;
        });
        return;
      }

      // Load danh sách appointments với filter theo patient_id
      final appointmentsResult = await AppointmentService.getAppointments(
        params: {'patient_id': patientId},
      );
      // Debug: In ra response server trả về
      print(
        '[DEBUG] appointmentsResult (full response): ${appointmentsResult.toString()}',
      );
      if (appointmentsResult['success'] != true) {
        print('[DEBUG] appointmentsResult: ${appointmentsResult.toString()}');
        setState(() {
          errorMessage =
              appointmentsResult['message'] ??
              'Không thể lấy danh sách lịch hẹn';
          isLoading = false;
        });
        return;
      }

      // Appointments đã được filter từ API, không cần filter thêm
      final filteredAppointments =
          (appointmentsResult['data']['rows'] as List<dynamic>?)
              ?.map((row) => row['doc'])
              .toList() ??
          [];

      setState(() {
        doctors = doctorsResult['data']['data'] ?? [];
        appointments = filteredAppointments;
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        errorMessage = 'Lỗi kết nối: $e';
        isLoading = false;
      });
    }
  }

  String _getDoctorName(String? doctorId) {
    if (doctorId == null) return 'Chưa xác định';
    try {
      final doctor = doctors.firstWhere(
        (doc) => doc['_id'] == doctorId,
        orElse: () => null,
      );
      if (doctor != null) {
        final personalInfo = doctor['personal_info'] ?? {};
        return personalInfo['full_name'] ?? 'Chưa xác định';
      }
      return 'Chưa xác định';
    } catch (e) {
      return 'Chưa xác định';
    }
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Lịch sử khám bệnh'),
          backgroundColor: Colors.blue.shade600,
        ),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (errorMessage != null) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Lịch sử khám bệnh'),
          backgroundColor: Colors.blue.shade600,
        ),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
              const SizedBox(height: 16),
              Text(errorMessage!, style: const TextStyle(fontSize: 16)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: _loadData,
                child: const Text('Thử lại'),
              ),
            ],
          ),
        ),
      );
    }

    if (appointments.isEmpty) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Lịch sử khám bệnh'),
          backgroundColor: Colors.blue.shade600,
        ),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.calendar_today, size: 64, color: Colors.grey.shade400),
              const SizedBox(height: 16),
              const Text(
                'Chưa có lịch hẹn nào',
                style: TextStyle(fontSize: 16, color: Colors.grey),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Lịch sử khám bệnh'),
        backgroundColor: Colors.blue.shade600,
      ),
      body: RefreshIndicator(
        onRefresh: _loadData,
        child: ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: appointments.length,
          itemBuilder: (context, index) {
            final appointment = appointments[index];
            return AppointmentCard(
              appointment: appointment,
              doctorName: _getDoctorName(appointment['doctor_id']),
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) =>
                        AppointmentDetailScreen(appointment: appointment),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

class AppointmentCard extends StatelessWidget {
  final Map<String, dynamic> appointment;
  final String doctorName;
  final VoidCallback onTap;

  const AppointmentCard({
    Key? key,
    required this.appointment,
    required this.doctorName,
    required this.onTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final appointmentInfo = appointment['appointment_info'] ?? {};
    final scheduledDate = appointmentInfo['scheduled_date'] ?? '';
    final duration = appointmentInfo['duration'] ?? 0;
    final status = appointment['status'] ?? '';

    Color statusColor;
    String statusText;
    switch (status) {
      case 'scheduled':
        statusColor = Colors.blue;
        statusText = 'Đã đặt';
        break;
      case 'completed':
        statusColor = Colors.green;
        statusText = 'Hoàn thành';
        break;
      case 'cancelled':
        statusColor = Colors.red;
        statusText = 'Đã hủy';
        break;
      default:
        statusColor = Colors.grey;
        statusText = 'Không rõ';
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      doctorName,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      statusText,
                      style: TextStyle(
                        color: statusColor,
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Icon(
                    Icons.calendar_today,
                    size: 16,
                    color: Colors.grey.shade600,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    scheduledDate,
                    style: TextStyle(color: Colors.grey.shade700),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(
                    Icons.access_time,
                    size: 16,
                    color: Colors.grey.shade600,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '$duration phút',
                    style: TextStyle(color: Colors.grey.shade700),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
