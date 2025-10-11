import 'package:flutter/material.dart';

class EditProfileScreen extends StatefulWidget {
  final Map<String, dynamic> patient;
  const EditProfileScreen({Key? key, required this.patient}) : super(key: key);

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  late TextEditingController _fullNameController;
  late TextEditingController _birthDateController;
  late TextEditingController _genderController;
  late TextEditingController _idNumberController;
  late TextEditingController _phoneController;
  late TextEditingController _emailController;
  late TextEditingController _emergencyNameController;
  late TextEditingController _emergencyRelationshipController;
  late TextEditingController _emergencyPhoneController;
  late TextEditingController _streetController;
  late TextEditingController _wardController;
  late TextEditingController _districtController;
  late TextEditingController _cityController;
  late TextEditingController _postalCodeController;

  @override
  void initState() {
    super.initState();
    final info = widget.patient['personal_info'] ?? {};
    final address = widget.patient['address'] ?? {};
    final emergency = info['emergency_contact'] ?? {};
    _fullNameController = TextEditingController(text: info['full_name'] ?? '');
    _birthDateController = TextEditingController(
      text: info['birth_date'] ?? '',
    );
    _genderController = TextEditingController(text: info['gender'] ?? '');
    _idNumberController = TextEditingController(text: info['id_number'] ?? '');
    _phoneController = TextEditingController(text: info['phone'] ?? '');
    _emailController = TextEditingController(text: info['email'] ?? '');
    _emergencyNameController = TextEditingController(
      text: emergency['name'] ?? '',
    );
    _emergencyRelationshipController = TextEditingController(
      text: emergency['relationship'] ?? '',
    );
    _emergencyPhoneController = TextEditingController(
      text: emergency['phone'] ?? '',
    );
    _streetController = TextEditingController(text: address['street'] ?? '');
    _wardController = TextEditingController(text: address['ward'] ?? '');
    _districtController = TextEditingController(
      text: address['district'] ?? '',
    );
    _cityController = TextEditingController(text: address['city'] ?? '');
    _postalCodeController = TextEditingController(
      text: address['postal_code'] ?? '',
    );
  }

  @override
  void dispose() {
    _fullNameController.dispose();
    _birthDateController.dispose();
    _genderController.dispose();
    _idNumberController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _emergencyNameController.dispose();
    _emergencyRelationshipController.dispose();
    _emergencyPhoneController.dispose();
    _streetController.dispose();
    _wardController.dispose();
    _districtController.dispose();
    _cityController.dispose();
    _postalCodeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Chỉnh sửa thông tin'),
        backgroundColor: Colors.blue.shade600,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionTitle('Thông tin cá nhân'),
            _buildTextField(_fullNameController, 'Họ tên'),
            _buildTextField(_birthDateController, 'Ngày sinh (yyyy-MM-dd)'),
            _buildTextField(_genderController, 'Giới tính'),
            _buildTextField(_idNumberController, 'Số CMND/CCCD'),
            _buildTextField(_phoneController, 'Số điện thoại'),
            _buildTextField(_emailController, 'Email'),
            const SizedBox(height: 24),
            _sectionTitle('Liên hệ khẩn cấp'),
            _buildTextField(_emergencyNameController, 'Tên liên hệ khẩn cấp'),
            _buildTextField(_emergencyRelationshipController, 'Mối quan hệ'),
            _buildTextField(_emergencyPhoneController, 'Số điện thoại liên hệ'),
            const SizedBox(height: 24),
            _sectionTitle('Địa chỉ'),
            _buildTextField(_streetController, 'Địa chỉ'),
            _buildTextField(_wardController, 'Phường/Xã'),
            _buildTextField(_districtController, 'Quận/Huyện'),
            _buildTextField(_cityController, 'Thành phố'),
            _buildTextField(_postalCodeController, 'Mã bưu điện'),
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue.shade600,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                onPressed: () {
                  // TODO: Gọi API cập nhật thông tin bệnh nhân
                },
                child: const Text(
                  'Lưu thay đổi',
                  style: TextStyle(fontSize: 16),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionTitle(String text) {
    return Padding(
      padding: const EdgeInsets.only(top: 4, bottom: 8),
      child: Text(
        text,
        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
      ),
    );
  }

  Widget _buildTextField(TextEditingController controller, String label) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: TextField(
        controller: controller,
        decoration: InputDecoration(
          labelText: label,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
    );
  }
}
