import 'package:flutter/material.dart';
import '../../api/patient_service.dart';
import '../../api/user_service.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({Key? key}) : super(key: key);

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic>? patient;
  bool isLoading = true;
  String? errorMessage;

  @override
  void initState() {
    super.initState();
    _loadPatientData();
  }

  Future<void> _loadPatientData() async {
    try {
      // Lấy thông tin user để có linked_staff_id hoặc patient_id
      final user = await UserService.getUser();
      if (user != null) {
        // Giả sử có trường linked_patient_id hoặc dùng linked_staff_id để tìm patient
        String patientId =
            user['linked_patient_id'] ??
            'patient_2024_001'; // ID mặc định cho demo

        final result = await PatientService.getPatientById(patientId);
        if (result['success'] == true) {
          setState(() {
            patient = result['data'];
            isLoading = false;
          });
        } else {
          setState(() {
            errorMessage =
                result['message'] ?? 'Không thể lấy thông tin bệnh nhân';
            isLoading = false;
          });
        }
      } else {
        setState(() {
          errorMessage = 'Không tìm thấy thông tin người dùng';
          isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        errorMessage = 'Lỗi kết nối: $e';
        isLoading = false;
      });
    }
  }

  // // Dữ liệu mẫu backup nếu không load được từ API
  // final Map<String, dynamic> _samplePatient = const {
  //   "_id": "patient_2024_001",
  //   "personal_info": {
  //     "full_name": "Nguyễn Văn An",
  //     "birth_date": "1985-03-15",
  //     "gender": "male",
  //     "id_number": "123456789",
  //     "phone": "0901234567",
  //     "email": "nguyen.van.an@email.com",
  //     "emergency_contact": {
  //       "name": "Nguyễn Thị Bình",
  //       "relationship": "spouse",
  //       "phone": "0907654321",
  //     },
  //   },
  //   "address": {
  //     "street": "123 Đường Lê Lợi",
  //     "ward": "Phường Bến Nghé",
  //     "district": "Quận 1",
  //     "city": "TP. Hồ Chí Minh",
  //     "postal_code": "700000",
  //   },
  //   "medical_info": {
  //     "blood_type": "A+",
  //     "allergies": ["Penicillin", "Peanuts"],
  //     "chronic_conditions": ["Hypertension"],
  //     "insurance": {
  //       "provider": "BHYT",
  //       "policy_number": "GD1234567890",
  //       "valid_until": "2024-12-31"
  //     }
  //   },
  // };

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Thông tin bệnh nhân'),
          backgroundColor: Colors.blue.shade600,
        ),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (errorMessage != null) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Thông tin bệnh nhân'),
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
                onPressed: () {
                  setState(() {
                    isLoading = true;
                    errorMessage = null;
                  });
                  _loadPatientData();
                },
                child: const Text('Thử lại'),
              ),
            ],
          ),
        ),
      );
    }

    final info = patient?["personal_info"];
    final address = patient?["address"];
    final medical = patient?["medical_info"];
    final emergency = info?["emergency_contact"];
    return Scaffold(
      appBar: AppBar(
        title: const Text('Thông tin bệnh nhân'),
        backgroundColor: Colors.blue.shade600,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 120),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Tiêu đề + avatar
            Row(
              children: [
                CircleAvatar(
                  radius: 32,
                  backgroundColor: Colors.blue.shade100,
                  child: Icon(
                    Icons.person,
                    size: 40,
                    color: Colors.blue.shade700,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Text(
                    info["full_name"],
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            // Thông tin cá nhân
            Text(
              'Thông tin cá nhân',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Card(
              elevation: 1,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _infoRow('Ngày sinh', info["birth_date"]),
                    _infoRow(
                      'Giới tính',
                      info["gender"] == "male" ? "Nam" : "Nữ",
                    ),
                    _infoRow('Số CMND/CCCD', info["id_number"]),
                    _infoRow('Số điện thoại', info["phone"]),
                    _infoRow('Email', info["email"]),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            // Liên hệ khẩn cấp
            Text(
              'Liên hệ khẩn cấp',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Card(
              elevation: 1,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _infoRow('Họ tên', emergency["name"]),
                    _infoRow(
                      'Mối quan hệ',
                      emergency["relationship"] == "spouse"
                          ? "Vợ/Chồng"
                          : emergency["relationship"],
                    ),
                    _infoRow('Số điện thoại', emergency["phone"]),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            // Thông tin y tế
            Text(
              'Thông tin y tế',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Card(
              elevation: 1,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _infoRow(
                      'Nhóm máu',
                      medical?["blood_type"] ?? 'Chưa xác định',
                    ),
                    _infoRow(
                      'Dị ứng',
                      medical?["allergies"]?.join(', ') ?? 'Không có',
                    ),
                    _infoRow(
                      'Bệnh mãn tính',
                      medical?["chronic_conditions"]?.join(', ') ?? 'Không có',
                    ),
                    if (medical?["insurance"] != null) ...[
                      _infoRow(
                        'Bảo hiểm',
                        medical["insurance"]["provider"] ?? '',
                      ),
                      _infoRow(
                        'Số thẻ BHYT',
                        medical["insurance"]["policy_number"] ?? '',
                      ),
                      _infoRow(
                        'Có hiệu lực đến',
                        medical["insurance"]["valid_until"] ?? '',
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            // Địa chỉ
            Text(
              'Địa chỉ',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Card(
              elevation: 1,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _infoRow('Địa chỉ', address["street"]),
                    _infoRow('Phường/Xã', address["ward"]),
                    _infoRow('Quận/Huyện', address["district"]),
                    _infoRow('Thành phố', address["city"]),
                    _infoRow('Mã bưu điện', address["postal_code"]),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 32),
            // Nút chức năng
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red.shade400,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    icon: const Icon(Icons.logout),
                    label: const Text(
                      'Đăng xuất',
                      style: TextStyle(fontSize: 16),
                    ),
                    onPressed: () {
                      // TODO: Thêm logic đăng xuất
                      // Ví dụ: await AuthService.logout();
                      // Navigator.pushReplacementNamed(context, '/login');
                    },
                  ),
                ),
                const SizedBox(width: 20),
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue.shade600,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    icon: const Icon(Icons.edit),
                    label: const Text(
                      'Chỉnh sửa',
                      style: TextStyle(fontSize: 16),
                    ),
                    onPressed: () {
                      // TODO: Chuyển sang màn hình chỉnh sửa thông tin
                      // Navigator.pushNamed(context, '/edit_profile', arguments: patient);
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _infoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          SizedBox(
            width: 120,
            child: Text(label, style: const TextStyle(color: Colors.grey)),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}
