import '../../api/patient_service.dart';
import 'package:flutter/material.dart';

class EditProfileScreen extends StatefulWidget {
  final Map<String, dynamic> patient;
  const EditProfileScreen({Key? key, required this.patient}) : super(key: key);

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _fullNameController;
  late TextEditingController _birthDateController;
  DateTime? _selectedBirthDate;
  String _gender = 'male';
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
  bool _isLoading = false;

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
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _sectionTitle('Thông tin cá nhân'),
              TextFormField(
                controller: _fullNameController,
                decoration: InputDecoration(
                  labelText: 'Họ tên',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                validator: (v) =>
                    (v == null || v.isEmpty) ? 'Vui lòng nhập họ tên' : null,
                textCapitalization: TextCapitalization.words,
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 14),
              GestureDetector(
                onTap: () async {
                  FocusScope.of(context).unfocus();
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: _selectedBirthDate ?? DateTime(2000, 1, 1),
                    firstDate: DateTime(1900),
                    lastDate: DateTime.now(),
                  );
                  if (picked != null) {
                    setState(() {
                      _selectedBirthDate = picked;
                      _birthDateController.text =
                          "${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}";
                    });
                  }
                },
                child: AbsorbPointer(
                  child: TextFormField(
                    controller: _birthDateController,
                    decoration: InputDecoration(
                      labelText: 'Ngày sinh (yyyy-MM-dd)',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    validator: (v) => (v == null || v.isEmpty)
                        ? 'Vui lòng chọn ngày sinh'
                        : null,
                    textInputAction: TextInputAction.next,
                  ),
                ),
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  const Padding(
                    padding: EdgeInsets.only(left: 4.0, right: 12),
                    child: Icon(Icons.wc, color: Colors.black54),
                  ),
                  const Text(
                    'Giới tính:',
                    style: TextStyle(fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: _gender,
                      items: const [
                        DropdownMenuItem(value: 'male', child: Text('Nam')),
                        DropdownMenuItem(value: 'female', child: Text('Nữ')),
                      ],
                      onChanged: (v) => setState(() => _gender = v ?? 'male'),
                      decoration: InputDecoration(
                        labelText: ' ',
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      validator: (v) =>
                          (v == null || v.isEmpty) ? 'Chọn giới tính' : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _idNumberController,
                decoration: InputDecoration(
                  labelText: 'Số CMND/CCCD (12 số)',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                keyboardType: TextInputType.number,
                validator: (v) {
                  if (v == null || v.isEmpty)
                    return 'Vui lòng nhập số CMND/CCCD';
                  if (!RegExp(r'^\d{12}$').hasMatch(v))
                    return 'Số CMND/CCCD phải gồm đúng 12 số';
                  return null;
                },
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _phoneController,
                decoration: InputDecoration(
                  labelText: 'Số điện thoại (10 số)',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                keyboardType: TextInputType.phone,
                validator: (v) {
                  if (v == null || v.isEmpty)
                    return 'Vui lòng nhập số điện thoại';
                  if (!RegExp(r'^\d{10}$').hasMatch(v))
                    return 'Số điện thoại phải gồm đúng 10 số';
                  return null;
                },
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _emailController,
                decoration: InputDecoration(
                  labelText: 'Email',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                keyboardType: TextInputType.emailAddress,
                validator: (v) {
                  if (v == null || v.isEmpty) return 'Vui lòng nhập email';
                  if (!RegExp(r'^[\w\.-]+@[\w\.-]+\.[a-zA-Z]{2,}$').hasMatch(v))
                    return 'Email không hợp lệ';
                  return null;
                },
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 24),
              _sectionTitle('Liên hệ khẩn cấp'),
              TextFormField(
                controller: _emergencyNameController,
                decoration: InputDecoration(
                  labelText: 'Tên liên hệ khẩn cấp',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                validator: (v) => (v == null || v.isEmpty)
                    ? 'Vui lòng nhập tên liên hệ khẩn cấp'
                    : null,
                textCapitalization: TextCapitalization.words,
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _emergencyRelationshipController,
                decoration: InputDecoration(
                  labelText: 'Mối quan hệ',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                validator: (v) => (v == null || v.isEmpty)
                    ? 'Vui lòng nhập mối quan hệ'
                    : null,
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _emergencyPhoneController,
                decoration: InputDecoration(
                  labelText: 'Số điện thoại liên hệ khẩn cấp (10 số)',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                keyboardType: TextInputType.phone,
                validator: (v) {
                  if (v == null || v.isEmpty)
                    return 'Vui lòng nhập số điện thoại liên hệ khẩn cấp';
                  if (!RegExp(r'^\d{10}$').hasMatch(v))
                    return 'Số điện thoại liên hệ phải gồm đúng 10 số';
                  return null;
                },
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 24),
              _sectionTitle('Địa chỉ'),
              TextFormField(
                controller: _streetController,
                decoration: InputDecoration(
                  labelText: 'Địa chỉ',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _wardController,
                decoration: InputDecoration(
                  labelText: 'Phường/Xã',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _districtController,
                decoration: InputDecoration(
                  labelText: 'Quận/Huyện',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _cityController,
                decoration: InputDecoration(
                  labelText: 'Thành phố',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _postalCodeController,
                decoration: InputDecoration(
                  labelText: 'Mã bưu điện',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                textInputAction: TextInputAction.done,
              ),
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
                  onPressed: _isLoading
                      ? null
                      : () async {
                          if (!_formKey.currentState!.validate()) return;
                          setState(() => _isLoading = true);
                          final patientId = widget.patient['_id'] ?? '';
                          final payload = {
                            '_id': patientId,
                            'type': 'patient',
                            'personal_info': {
                              'full_name': _fullNameController.text.trim(),
                              'birth_date': _birthDateController.text.trim(),
                              'gender': _gender,
                              'id_number': _idNumberController.text.trim(),
                              'phone': _phoneController.text.trim(),
                              'email': _emailController.text.trim(),
                              'emergency_contact': {
                                'name': _emergencyNameController.text.trim(),
                                'relationship': _emergencyRelationshipController
                                    .text
                                    .trim(),
                                'phone': _emergencyPhoneController.text.trim(),
                              },
                            },
                            'address': {
                              'street': _streetController.text.trim(),
                              'ward': _wardController.text.trim(),
                              'district': _districtController.text.trim(),
                              'city': _cityController.text.trim(),
                              'postal_code': _postalCodeController.text.trim(),
                            },
                          };

                          // Gọi API cập nhật
                          final result = await PatientService.updatePatient(
                            patientId,
                            payload,
                          );
                          setState(() => _isLoading = false);
                          if (result['success'] == true) {
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Cập nhật thành công!'),
                                ),
                              );
                              Navigator.of(
                                context,
                              ).pop(true); // Trả về true để reload profile
                            }
                          } else {
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(
                                    result['message'] ?? 'Cập nhật thất bại',
                                  ),
                                ),
                              );
                            }
                          }
                        },
                  child: _isLoading
                      ? const SizedBox(
                          height: 22,
                          width: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Text(
                          'Lưu thay đổi',
                          style: TextStyle(fontSize: 16),
                        ),
                ),
              ),
            ],
          ),
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
}
