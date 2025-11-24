import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../api/auth_service.dart';
import '../../api/patient_service.dart';
import 'dart:convert';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();

  // Controllers
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  final _fullNameController = TextEditingController();
  final _birthDateController = TextEditingController();
  DateTime? _selectedBirthDate;
  String _gender = 'male';
  final _idNumberController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _emergencyNameController = TextEditingController();
  final _emergencyRelationshipController = TextEditingController();
  final _emergencyPhoneController = TextEditingController();

  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _fullNameController.dispose();
    _birthDateController.dispose();
    _idNumberController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _emergencyNameController.dispose();
    _emergencyRelationshipController.dispose();
    _emergencyPhoneController.dispose();
    super.dispose();
  }

  double get _passwordStrength {
    final v = _passwordController.text;
    if (v.isEmpty) return 0;
    int score = 0;
    if (v.length >= 8) score++;
    if (RegExp(r'[A-Z]').hasMatch(v)) score++;
    if (RegExp(r'[a-z]').hasMatch(v)) score++;
    if (RegExp(r'\d').hasMatch(v)) score++;
    if (RegExp(r'[!@#\$%\^&\*\(\)_\+\-=\[\]\{\};:"\\|,.<>\/?]').hasMatch(v))
      score++;
    return (score / 5).clamp(0, 1).toDouble();
  }

  Future<void> _pickBirthDate() async {
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
  }

  InputDecoration _decoration({
    required String label,
    required IconData icon,
    Widget? suffixIcon,
    String? hint,
  }) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      prefixIcon: Icon(icon),
      suffixIcon: suffixIcon,
      filled: true,
      fillColor: Colors.grey.shade50,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.6),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Colors.red),
      ),
    );
  }

  void _register() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);

    try {
      // Tạo ID cho user và patient
      final userId = _generateUserId();
      final patientId = _generatePatientId();

      // Tạo user data
      final userData = {
        '_id': userId,
        'type': 'user',
        'username': _usernameController.text,
        'email': _emailController.text,
        'password': _passwordController.text, // Server sẽ tự hash thành password_hash
        'role_names': ['patient'],
        'account_type': 'patient',
        'linked_patient_id': patientId,
        'status': 'active',
        'created_at': DateTime.now().toIso8601String(),
        'updated_at': DateTime.now().toIso8601String(),
      };

      // Tạo patient data (luôn gửi emergency_contact, kể cả khi rỗng)
      final patientData = {
        '_id': patientId,
        'type': 'patient',
        'personal_info': {
          'full_name': _fullNameController.text,
          'birth_date': _birthDateController.text,
          'gender': _gender,
          'id_number': _idNumberController.text,
          'phone': _phoneController.text,
          'email': _emailController.text,
          'emergency_contact': {
            'name': _emergencyNameController.text.isNotEmpty ? _emergencyNameController.text : '',
            'relationship': _emergencyRelationshipController.text.isNotEmpty ? _emergencyRelationshipController.text : '',
            'phone': _emergencyPhoneController.text.isNotEmpty ? _emergencyPhoneController.text : '',
          },
        },
      };

      print('=== DEBUG: DỮ LIỆU GỬI LÊN SERVER ===');
      print('[REGISTER] User JSON: ${jsonEncode(userData)}');
      print('[REGISTER] Patient JSON: ${jsonEncode(patientData)}');
      debugPrint('[REGISTER] User JSON: ${jsonEncode(userData)}');
      debugPrint('[REGISTER] Patient JSON: ${jsonEncode(patientData)}');

      // Gọi API tạo user trước
      final userResult = await _createUser(userData);
      print('[REGISTER] User API response: $userResult');
      if (!userResult['success']) {
        print('[REGISTER] User API error: ${userResult['message']}');
        throw Exception('Tạo user thất bại: ${userResult['message']}');
      }

      // Sau đó tạo patient
      final patientResult = await _createPatient(patientData);
      print('[REGISTER] Patient API response: $patientResult');
      if (!patientResult['success']) {
        print('[REGISTER] Patient API error: ${patientResult['message']}');
        throw Exception('Tạo patient thất bại: ${patientResult['message']}');
      }

      setState(() => _isLoading = false);
      if (!mounted) return;

      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Đăng ký'),
          content: const Text('Đăng ký thành công!'),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                Navigator.of(context).pop(); // Về màn hình login
              },
              child: const Text('OK'),
            ),
          ],
        ),
      );
    } catch (e) {
      setState(() => _isLoading = false);
      if (!mounted) return;

      // Log rõ lỗi tạo user hay patient thiếu token
      final errorMsg = e.toString();
      if (errorMsg.contains('user') && errorMsg.contains('token')) {
        print('[REGISTER] Exception: Lỗi tạo user: $errorMsg');
      } else if (errorMsg.contains('patient') && errorMsg.contains('token')) {
        print('[REGISTER] Exception: Lỗi tạo patient: $errorMsg');
      } else {
        print('[REGISTER] Exception: $errorMsg');
      }

      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Lỗi'),
          content: Text('Đăng ký thất bại: $e'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('OK'),
            ),
          ],
        ),
      );
    }
  }

  String _generateUserId() {
    final now = DateTime.now();
    return 'user_patient_${now.second}${now.minute}${now.hour}${now.day}${now.month}${now.year}';
  }

  String _generatePatientId() {
    final now = DateTime.now();
    return 'patient_${now.second}${now.minute}${now.hour}${now.day}${now.month}${now.year}';
  }

  Future<Map<String, dynamic>> _createUser(
    Map<String, dynamic> userData,
  ) async {
    return await AuthService.createUser(userData);
  }

  Future<Map<String, dynamic>> _createPatient(
    Map<String, dynamic> patientData,
  ) async {
    return await PatientService.createPatient(patientData);
  }

  Widget _sectionTitle(String text) {
    return Padding(
      padding: const EdgeInsets.only(top: 4, bottom: 8),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Text(
          text,
          style: const TextStyle(
            fontWeight: FontWeight.w700,
            fontSize: 16,
            color: Color(0xFF0F172A),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final maxWidth = 760.0;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF0F172A),
        title: const Text('Đăng ký tài khoản'),
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: BoxConstraints(maxWidth: maxWidth),
            child: Padding(
              padding: const EdgeInsets.all(20.0),
              child: Card(
                elevation: 2,
                shadowColor: Colors.black12,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Form(
                    key: _formKey,
                    // LƯU Ý: KHÔNG đặt autovalidateMode ở đây
                    child: SingleChildScrollView(
                      keyboardDismissBehavior:
                          ScrollViewKeyboardDismissBehavior.onDrag,
                      padding: EdgeInsets.only(
                        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const SizedBox(height: 8),
                          _sectionTitle('Thông tin đăng nhập'),

                          TextFormField(
                            controller: _usernameController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            decoration: _decoration(
                              label: 'Tên đăng nhập',
                              icon: Icons.person,
                            ),
                            validator: (v) {
                              if (v == null || v.isEmpty) {
                                return 'Vui lòng nhập tên đăng nhập';
                              }
                              if (v.length < 4) {
                                return 'Tên đăng nhập tối thiểu 4 ký tự';
                              }
                              return null;
                            },
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 14),

                          TextFormField(
                            controller: _passwordController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            obscureText: _obscurePassword,
                            decoration: _decoration(
                              label: 'Mật khẩu',
                              icon: Icons.lock,
                              suffixIcon: IconButton(
                                onPressed: () => setState(
                                  () => _obscurePassword = !_obscurePassword,
                                ),
                                icon: Icon(
                                  _obscurePassword
                                      ? Icons.visibility
                                      : Icons.visibility_off,
                                ),
                              ),
                            ),
                            onChanged: (_) => setState(() {}),
                            validator: (v) {
                              if (v == null || v.isEmpty) {
                                return 'Vui lòng nhập mật khẩu';
                              }
                              if (v.length < 8) {
                                return 'Mật khẩu tối thiểu 8 ký tự';
                              }
                              return null;
                            },
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 8),
                          // Strength bar
                          ClipRRect(
                            borderRadius: BorderRadius.circular(6),
                            child: LinearProgressIndicator(
                              value: _passwordStrength,
                              minHeight: 6,
                              backgroundColor: Colors.grey.shade200,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _passwordStrength >= 0.8
                                ? 'Mật khẩu mạnh'
                                : _passwordStrength >= 0.5
                                ? 'Mật khẩu trung bình'
                                : 'Mật khẩu yếu',
                            style: TextStyle(
                              fontSize: 12,
                              color: _passwordStrength >= 0.8
                                  ? Colors.green
                                  : _passwordStrength >= 0.5
                                  ? Colors.orange
                                  : Colors.red,
                            ),
                          ),
                          const SizedBox(height: 14),

                          TextFormField(
                            controller: _confirmPasswordController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            obscureText: _obscureConfirmPassword,
                            decoration: _decoration(
                              label: 'Xác nhận mật khẩu',
                              icon: Icons.lock_outline,
                              suffixIcon: IconButton(
                                onPressed: () => setState(
                                  () => _obscureConfirmPassword =
                                      !_obscureConfirmPassword,
                                ),
                                icon: Icon(
                                  _obscureConfirmPassword
                                      ? Icons.visibility
                                      : Icons.visibility_off,
                                ),
                              ),
                            ),
                            validator: (v) {
                              if (v == null || v.isEmpty) {
                                return 'Vui lòng nhập lại mật khẩu';
                              }
                              if (v != _passwordController.text) {
                                return 'Mật khẩu xác nhận không khớp';
                              }
                              return null;
                            },
                            textInputAction: TextInputAction.next,
                          ),

                          const SizedBox(height: 22),
                          _sectionTitle('Thông tin cá nhân'),

                          TextFormField(
                            controller: _fullNameController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            decoration: _decoration(
                              label: 'Họ tên',
                              icon: Icons.badge,
                            ),
                            validator: (v) => (v == null || v.isEmpty)
                                ? 'Vui lòng nhập họ tên'
                                : null,
                            textInputAction: TextInputAction.next,
                            textCapitalization: TextCapitalization.words,
                          ),
                          const SizedBox(height: 14),

                          GestureDetector(
                            onTap: _pickBirthDate,
                            child: AbsorbPointer(
                              child: TextFormField(
                                controller: _birthDateController,
                                autovalidateMode:
                                    AutovalidateMode.onUserInteraction,
                                decoration: _decoration(
                                  label: 'Ngày sinh (yyyy-MM-dd)',
                                  icon: Icons.cake,
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
                                  autovalidateMode:
                                      AutovalidateMode.onUserInteraction,
                                  items: const [
                                    DropdownMenuItem(
                                      value: 'male',
                                      child: Text('Nam'),
                                    ),
                                    DropdownMenuItem(
                                      value: 'female',
                                      child: Text('Nữ'),
                                    ),
                                  ],
                                  onChanged: (v) =>
                                      setState(() => _gender = v ?? 'male'),
                                  decoration: _decoration(
                                    label: ' ',
                                    icon: Icons.transgender,
                                  ),
                                  validator: (v) => (v == null || v.isEmpty)
                                      ? 'Chọn giới tính'
                                      : null,
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 22),
                          _sectionTitle('Liên hệ & giấy tờ'),

                          TextFormField(
                            controller: _idNumberController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            decoration: _decoration(
                              label: 'Số CMND/CCCD (12 số)',
                              icon: Icons.credit_card,
                            ),
                            keyboardType: TextInputType.number,
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                              LengthLimitingTextInputFormatter(12),
                            ],
                            validator: (v) {
                              if (v == null || v.isEmpty) {
                                return 'Vui lòng nhập số CMND/CCCD';
                              }
                              if (!RegExp(r'^\d{12}$').hasMatch(v)) {
                                return 'Số CMND/CCCD phải gồm đúng 12 số';
                              }
                              return null;
                            },
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 14),

                          TextFormField(
                            controller: _phoneController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            decoration: _decoration(
                              label: 'Số điện thoại (10 số)',
                              icon: Icons.phone,
                            ),
                            keyboardType: TextInputType.phone,
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                              LengthLimitingTextInputFormatter(10),
                            ],
                            validator: (v) {
                              if (v == null || v.isEmpty) {
                                return 'Vui lòng nhập số điện thoại';
                              }
                              if (!RegExp(r'^\d{10}$').hasMatch(v)) {
                                return 'Số điện thoại phải gồm đúng 10 số';
                              }
                              return null;
                            },
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 14),

                          TextFormField(
                            controller: _emailController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            decoration: _decoration(
                              label: 'Email',
                              icon: Icons.email,
                            ),
                            keyboardType: TextInputType.emailAddress,
                            validator: (v) {
                              if (v == null || v.isEmpty) {
                                return 'Vui lòng nhập email';
                              }
                              if (!RegExp(
                                r'^[\w\.-]+@[\w\.-]+\.[a-zA-Z]{2,}$',
                              ).hasMatch(v)) {
                                return 'Email không hợp lệ';
                              }
                              return null;
                            },
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 22),
                          _sectionTitle('Liên hệ khẩn cấp'),

                          TextFormField(
                            controller: _emergencyNameController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            decoration: _decoration(
                              label: 'Tên liên hệ khẩn cấp',
                              icon: Icons.contact_phone,
                            ),
                            validator: (v) => (v == null || v.isEmpty)
                                ? 'Vui lòng nhập tên liên hệ khẩn cấp'
                                : null,
                            textInputAction: TextInputAction.next,
                            textCapitalization: TextCapitalization.words,
                          ),
                          const SizedBox(height: 14),

                          TextFormField(
                            controller: _emergencyRelationshipController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            decoration: _decoration(
                              label: 'Mối quan hệ',
                              icon: Icons.group,
                            ),
                            validator: (v) => (v == null || v.isEmpty)
                                ? 'Vui lòng nhập mối quan hệ'
                                : null,
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 14),

                          TextFormField(
                            controller: _emergencyPhoneController,
                            autovalidateMode:
                                AutovalidateMode.onUserInteraction,
                            decoration: _decoration(
                              label: 'Số điện thoại liên hệ khẩn cấp (10 số)',
                              icon: Icons.phone_in_talk,
                            ),
                            keyboardType: TextInputType.phone,
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                              LengthLimitingTextInputFormatter(10),
                            ],
                            validator: (v) {
                              if (v == null || v.isEmpty) {
                                return 'Vui lòng nhập số điện thoại liên hệ khẩn cấp';
                              }
                              if (!RegExp(r'^\d{10}$').hasMatch(v)) {
                                return 'Số điện thoại liên hệ phải gồm đúng 10 số';
                              }
                              return null;
                            },
                            textInputAction: TextInputAction.done,
                          ),

                          const SizedBox(height: 24),
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: () =>
                                      _formKey.currentState?.validate(),
                                  style: OutlinedButton.styleFrom(
                                    foregroundColor: const Color(0xFFDC2626),
                                    side: const BorderSide(
                                      color: Color(0xFFDC2626),
                                    ),
                                    padding: const EdgeInsets.symmetric(
                                      vertical: 14,
                                    ),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                  ),
                                  child: const Text(
                                    'Kiểm tra lỗi',
                                    style: TextStyle(
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: ElevatedButton(
                                  onPressed: _isLoading ? null : _register,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFF2563EB),
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(
                                      vertical: 14,
                                    ),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    elevation: 2,
                                  ),
                                  child: _isLoading
                                      ? const SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                            valueColor:
                                                AlwaysStoppedAnimation<Color>(
                                                  Colors.white,
                                                ),
                                          ),
                                        )
                                      : const Text(
                                          'Đăng ký',
                                          style: TextStyle(
                                            fontSize: 16,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
