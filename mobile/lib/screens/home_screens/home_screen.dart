import 'package:flutter/material.dart';
import '../../api/appointment_service.dart';
import '../../api/doctor_service.dart';
import '../../api/user_service.dart';
import 'package:intl/intl.dart';

class HomeScreenContent extends StatefulWidget {
  @override
  State<HomeScreenContent> createState() => _HomeScreenContentState();
}

class _HomeScreenContentState extends State<HomeScreenContent> {
  final _formKey = GlobalKey<FormState>();
  
  // Form controllers
  final _reasonController = TextEditingController();
  final _notesController = TextEditingController();
  
  // Form values
  String? _currentPatientId; // Bệnh nhân đang đăng nhập
  String? _selectedDoctorId;
  DateTime _selectedDate = DateTime.now();
  TimeOfDay? _selectedTime; // Sẽ chọn từ khung giờ trống
  int _duration = 30;
  String _appointmentType = 'consultation';
  List<int> _availableDurations = [15, 30, 45, 60, 90, 120]; // Duration khả dụng dựa trên giờ chọn
  
  // Data lists
  Map<String, dynamic>? _currentPatient;
  List<dynamic> _doctors = [];
  List<Map<String, dynamic>> _availableTimeSlots = []; // Khung giờ trống
  List<dynamic> _doctorAppointments = []; // Lịch hẹn của bác sĩ
  bool _isLoading = false;
  bool _isSubmitting = false;
  bool _isLoadingTimeSlots = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _reasonController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
    });

    try {
      // 1. Lấy thông tin user đang đăng nhập
      final user = await UserService.getUser();
      print('🟢 [DEBUG] UserService.getUser() = $user');
      if (user == null) {
        _showError('Không tìm thấy thông tin user trong local storage');
        setState(() { _isLoading = false; });
        return;
      }
      if (!user.containsKey('linked_patient_id') || user['linked_patient_id'] == null) {
        _showError('User không có trường linked_patient_id hoặc giá trị null');
        setState(() { _isLoading = false; });
        return;
      }
      if (!user.containsKey('id') || user['id'] == null) {
        _showError('User không có trường id hoặc giá trị null');
        setState(() { _isLoading = false; });
        return;
      }

      _currentPatientId = user['linked_patient_id'] is String ? user['linked_patient_id'] : user['linked_patient_id']?.toString();
      print('🔍 Current patient ID: $_currentPatientId');
      print('🟢 [DEBUG] user id: ${user['id']} (type: ${user['id']?.runtimeType})');

      // 2. Lấy thông tin chi tiết bệnh nhân
      final userResult = await UserService.getUserById(user['id'].toString());
      print('🟢 [DEBUG] UserService.getUserById(${user['id']}) = $userResult');
      if (userResult['success'] == true) {
        final userData = userResult['data'];
        print('🟢 [DEBUG] userResult["data"] = $userData');
        if (userData != null && userData['linked_patient_id'] != null) {
          _currentPatient = {
            '_id': userData['linked_patient_id'],
            'personal_info': {
              'full_name': userData['username'] ?? 'Bệnh nhân',
            }
          };
        } else {
          print('🟡 [DEBUG] userData null hoặc không có linked_patient_id');
        }
      } else {
        print('🟡 [DEBUG] userResult không success: $userResult');
      }

      // 3. Lấy danh sách bác sĩ
      final doctorsResult = await DoctorService.getDoctors();
      print('📋 Doctors result: ${doctorsResult['success']}');
      
      if (doctorsResult['success'] == true) {
        final data = doctorsResult['data'];
        print('📊 Doctors data type: ${data.runtimeType}');
        print('📊 Doctors data: $data');
        
        if (data is Map && data.containsKey('rows')) {
          final rows = data['rows'] as List<dynamic>?;
          if (rows != null) {
            _doctors = rows.map((row) => row['doc']).toList();
            print('✅ Loaded ${_doctors.length} doctors');
          }
        } else if (data is List) {
          _doctors = data;
          print('✅ Loaded ${_doctors.length} doctors (direct list)');
        }
      } else {
        print('❌ Failed to load doctors: ${doctorsResult['message']}');
      }
    } catch (e, stack) {
      print('❌ Error loading data: $e');
      print('❌ STACKTRACE: $stack');
      _showError('Lỗi khi tải dữ liệu: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  // Tải lịch hẹn của bác sĩ trong ngày đã chọn
  Future<void> _loadDoctorAppointments() async {
    if (_selectedDoctorId == null) return;

    setState(() {
      _isLoadingTimeSlots = true;
      _availableTimeSlots = [];
      _selectedTime = null;
    });

    try {
      // Lấy tất cả appointment của bác sĩ
      final result = await AppointmentService.getAppointments();
      
      if (result['success'] == true) {
        final data = result['data'];
        final rows = data['rows'] as List<dynamic>?;
        
        if (rows != null) {
          // Lọc appointment của bác sĩ trong ngày đã chọn
          _doctorAppointments = rows
              .map((row) => row['doc'])
              .where((appointment) {
                if (appointment['doctor_id'] != _selectedDoctorId) return false;
                
                final appointmentInfo = appointment['appointment_info'];
                if (appointmentInfo == null) return false;
                
                final scheduledDate = appointmentInfo['scheduled_date'];
                if (scheduledDate == null) return false;
                
                try {
                  final date = DateTime.parse(scheduledDate);
                  // Chỉ lấy appointment trong ngày đã chọn
                  return date.year == _selectedDate.year &&
                      date.month == _selectedDate.month &&
                      date.day == _selectedDate.day;
                } catch (e) {
                  return false;
                }
              })
              .toList();

          print('📅 Found ${_doctorAppointments.length} appointments for selected date');
          _calculateAvailableTimeSlots();
        }
      }
    } catch (e) {
      print('❌ Error loading appointments: $e');
      _showError('Lỗi khi tải lịch hẹn: $e');
    } finally {
      setState(() {
        _isLoadingTimeSlots = false;
      });
    }
  }

  // Tính toán khung giờ trống (7h-17h, trừ giờ break 12h-13h)
  void _calculateAvailableTimeSlots() {
    _availableTimeSlots = [];
    
    const workStart = 7; // 7h
    const workEnd = 17; // 17h
    const breakStart = 12; // 12h
    const breakEnd = 13; // 13h
    
    // Tạo danh sách các khoảng thời gian bận
    List<Map<String, DateTime>> busySlots = [];
    for (var appointment in _doctorAppointments) {
      try {
        final appointmentInfo = appointment['appointment_info'];
        final scheduledDateStr = appointmentInfo['scheduled_date'];
        
        // Backend lưu giờ địa phương nhưng ghi 'Z', cần bỏ 'Z' để parse đúng
        final localDateStr = scheduledDateStr.replaceAll('Z', '');
        final scheduledDate = DateTime.parse(localDateStr);
        final duration = appointmentInfo['duration'] ?? 30;
        
        print('🕐 Appointment: ${appointment['_id']}');
        print('   Raw: $scheduledDateStr');
        print('   Parsed as Local: $scheduledDate');
        print('   Duration: $duration minutes');
        
        busySlots.add({
          'start': scheduledDate,
          'end': scheduledDate.add(Duration(minutes: duration)),
        });
      } catch (e) {
        print('❌ Error parsing appointment time: $e');
      }
    }
    
    // Sắp xếp theo thời gian bắt đầu
    busySlots.sort((a, b) => a['start']!.compareTo(b['start']!));
    
    print('🔍 Busy slots: ${busySlots.map((s) => '${DateFormat('HH:mm').format(s['start']!)} - ${DateFormat('HH:mm').format(s['end']!)}')}');
    
    // Tạo tất cả các khung giờ từ 7h-17h, mỗi khung 15 phút
    DateTime currentTime = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      workStart,
    );
    
    final endTime = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      workEnd,
    );
    
    final breakStartTime = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      breakStart,
    );
    
    final breakEndTime = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      breakEnd,
    );
    
    // Tạo các khung giờ 15 phút
    while (currentTime.isBefore(endTime)) {
      // Bỏ qua giờ nghỉ trưa (CHỈ KIỂM TRA GIỜ BẮT ĐẦU)
      if (currentTime.hour >= breakStart && currentTime.hour < breakEnd) {
        currentTime = currentTime.add(const Duration(minutes: 15));
        continue;
      }
      
      // Kiểm tra xung đột với các lịch hẹn đã có
      // CHỈ kiểm tra xem GIỜ BẮT ĐẦU có nằm trong khoảng bận không
      bool hasConflict = false;
      for (var busySlot in busySlots) {
        final busyStart = busySlot['start']!;
        final busyEnd = busySlot['end']!;
        
        // Chỉ kiểm tra: giờ bắt đầu có nằm trong khoảng bận không
        if (currentTime.isAtSameMomentAs(busyStart) || 
            (currentTime.isAfter(busyStart) && currentTime.isBefore(busyEnd))) {
          hasConflict = true;
          break;
        }
      }
      
      if (!hasConflict) {
        _availableTimeSlots.add({
          'start': currentTime,
          'end': currentTime.add(Duration(minutes: _duration)),
        });
      }
      
      currentTime = currentTime.add(const Duration(minutes: 15));
    }
    
    print('✅ Available time slots (${_availableTimeSlots.length}): ${_availableTimeSlots.map((s) => DateFormat('HH:mm').format(s['start']!))}');
  }

  // Tính toán các duration khả dụng dựa trên giờ đã chọn
  void _calculateAvailableDurations() {
    if (_selectedTime == null) {
      setState(() {
        _availableDurations = [15, 30, 45, 60, 90, 120];
      });
      return;
    }

    final selectedDateTime = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      _selectedTime!.hour,
      _selectedTime!.minute,
    );

    // Các giới hạn thời gian
    final lunchStart = DateTime(_selectedDate.year, _selectedDate.month, _selectedDate.day, 12, 0);
    final lunchEnd = DateTime(_selectedDate.year, _selectedDate.month, _selectedDate.day, 13, 0);
    final endOfDay = DateTime(_selectedDate.year, _selectedDate.month, _selectedDate.day, 17, 0);

    // Tìm giới hạn gần nhất (giờ nghỉ, giờ tan, hoặc appointment)
    DateTime? nearestLimit;

    // 1. Kiểm tra giờ nghỉ trưa
    if (selectedDateTime.isBefore(lunchStart)) {
      nearestLimit = lunchStart;
    }

    // 2. Kiểm tra giờ tan làm
    if (nearestLimit == null || endOfDay.isBefore(nearestLimit)) {
      nearestLimit = endOfDay;
    }

    // 3. Kiểm tra appointment tiếp theo
    for (var appointment in _doctorAppointments) {
      try {
        final appointmentInfo = appointment['appointment_info'];
        final scheduledDateStr = appointmentInfo['scheduled_date'];
        final localDateStr = scheduledDateStr.replaceAll('Z', '');
        final appointmentStart = DateTime.parse(localDateStr);

        // Chỉ quan tâm appointment SAU giờ đã chọn
        if (appointmentStart.isAfter(selectedDateTime)) {
          if (nearestLimit == null || appointmentStart.isBefore(nearestLimit)) {
            nearestLimit = appointmentStart;
          }
        }
      } catch (e) {
        print('❌ Error checking appointment limit: $e');
      }
    }

    // Tính khoảng thời gian tối đa (phút)
    final maxMinutes = nearestLimit != null 
        ? nearestLimit.difference(selectedDateTime).inMinutes 
        : 120;

    print('🕐 Selected time: ${DateFormat('HH:mm').format(selectedDateTime)}');
    print('⏰ Nearest limit: ${nearestLimit != null ? DateFormat('HH:mm').format(nearestLimit) : "none"}');
    print('⏱️  Max duration: $maxMinutes minutes');

    // Lọc các duration hợp lệ
    final validDurations = [15, 30, 45, 60, 90, 120]
        .where((duration) => duration <= maxMinutes)
        .toList();

    // Đảm bảo luôn có ít nhất 1 duration
    if (validDurations.isEmpty) {
      validDurations.add(15);
    }

    setState(() {
      _availableDurations = validDurations;
      
      // Nếu duration hiện tại không hợp lệ, chọn duration lớn nhất khả dụng
      if (!_availableDurations.contains(_duration)) {
        _duration = _availableDurations.last;
      }
    });

    print('✅ Available durations: $_availableDurations, selected: $_duration');
  }

  // Helper: Lấy duration hợp lệ cho dropdown
  int _getValidDuration() {
    if (_availableDurations.contains(_duration)) {
      return _duration;
    }
    if (_availableDurations.isNotEmpty) {
      return _availableDurations.first;
    }
    return 15;
  }

  // Helper: Lấy danh sách duration items cho dropdown
  List<DropdownMenuItem<int>> _getDurationItems() {
    final durations = _availableDurations.isEmpty 
        ? [15, 30, 45, 60, 90, 120] 
        : _availableDurations;
    
    return durations.map((duration) {
      return DropdownMenuItem<int>(
        value: duration,
        child: Text('$duration phút'),
      );
    }).toList();
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null && picked != _selectedDate) {
      setState(() {
        _selectedDate = picked;
        _selectedTime = null; // Reset time khi đổi ngày
      });
      // Load lại lịch của bác sĩ
      if (_selectedDoctorId != null) {
        _loadDoctorAppointments();
      }
    }
  }

  Future<void> _submitAppointment() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    if (_currentPatientId == null || _selectedDoctorId == null) {
      _showError('Vui lòng chọn bác sĩ');
      return;
    }

    if (_selectedTime == null) {
      _showError('Vui lòng chọn khung giờ khám');
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    try {
      // Tạo DateTime UTC nhưng giữ nguyên giờ người dùng chọn
      final scheduledDateTime = DateTime.utc(
        _selectedDate.year,
        _selectedDate.month,
        _selectedDate.day,
        _selectedTime!.hour,
        _selectedTime!.minute,
      );

      final appointmentEndTime = scheduledDateTime.add(Duration(minutes: _duration));

      // Kiểm tra xung đột với các lịch hẹn khác (CHỈ KIỂM TRA GIỜ BẮT ĐẦU)
      bool hasConflict = false;
      for (var appointment in _doctorAppointments) {
        try {
          final appointmentInfo = appointment['appointment_info'];
          final existingStartStr = appointmentInfo['scheduled_date'];
          final localStartStr = existingStartStr.replaceAll('Z', '');
          final existingStart = DateTime.parse(localStartStr);
          final existingDuration = appointmentInfo['duration'] ?? 30;
          final existingEnd = existingStart.add(Duration(minutes: existingDuration));

          // CHỈ kiểm tra: giờ bắt đầu mới có nằm trong khoảng appointment cũ không
          if (scheduledDateTime.isAtSameMomentAs(existingStart) ||
              (scheduledDateTime.isAfter(existingStart) && scheduledDateTime.isBefore(existingEnd))) {
            hasConflict = true;
            _showError('Khung giờ này bị trùng với lịch hẹn khác!\n'
                'Lịch hẹn hiện có: ${DateFormat('HH:mm').format(existingStart)} - '
                '${DateFormat('HH:mm').format(existingEnd)}');
            break;
          }
        } catch (e) {
          print('❌ Error checking conflict: $e');
        }
      }

      if (hasConflict) {
        setState(() {
          _isSubmitting = false;
        });
        return;
      }

      // Lấy user đang đăng nhập để lấy id cho created_by
      final user = await UserService.getUser();
      final createdBy = user != null && user['id'] != null ? user['id'].toString() : '';

      // Sinh _id: appointment + yyyyMMddHHmmss
    final now = DateTime.now().toUtc();
      String twoDigits(int n) => n.toString().padLeft(2, '0');
      final idStr = 'appointment'
        + now.year.toString()
        + twoDigits(now.month)
        + twoDigits(now.day)
        + twoDigits(now.hour)
        + twoDigits(now.minute)
        + twoDigits(now.second);

      // Reminder gửi trước 1 tiếng
      final reminderTime = scheduledDateTime.subtract(const Duration(hours: 1));
      // Hàm xuất ISO8601 UTC luôn có hậu tố Z
      String toIso8601Z(DateTime dt) {
        // Đảm bảo luôn trả về dạng ...Z, không có +00:00
        String s = dt.toUtc().toIso8601String();
        if (s.endsWith('Z')) return s;
        if (s.contains('+00:00')) return s.replaceAll('+00:00', 'Z');
        return s + 'Z';
      }

      final reminders = [
        {
          'type': 'sms',
          'sent_at': toIso8601Z(reminderTime),
          'status': 'pending',
        }
      ];

      final appointmentData = {
        '_id': idStr,
        'type': 'appointment',
        'patient_id': _currentPatientId,
        'doctor_id': _selectedDoctorId,
        'appointment_info': {
          'scheduled_date': toIso8601Z(scheduledDateTime),
          'duration': _duration,
          'type': _appointmentType,
          'priority': 'normal',
        },
        'reason': _reasonController.text,
        'status': 'scheduled',
        'notes': _notesController.text,
        'reminders': reminders,
        'created_by': createdBy,
    'created_at': toIso8601Z(now),
    'updated_at': toIso8601Z(now),
      };

      final result = await AppointmentService.createAppointment(appointmentData);

      if (result['success'] == true) {
        _showSuccess('Đã tạo lịch hẹn thành công!');
        _resetForm();
      } else {
        _showError(result['message'] ?? 'Không thể tạo lịch hẹn');
      }
    } catch (e) {
      _showError('Lỗi: $e');
    } finally {
      setState(() {
        _isSubmitting = false;
      });
    }
  }

  void _resetForm() {
    _formKey.currentState?.reset();
    _reasonController.clear();
    _notesController.clear();
    setState(() {
      _selectedDoctorId = null;
      _selectedDate = DateTime.now();
      _selectedTime = null;
      _duration = 30;
      _appointmentType = 'consultation';
      _availableTimeSlots = [];
      _doctorAppointments = [];
      _availableDurations = [15, 30, 45, 60, 90, 120];
    });
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Tạo lịch hẹn mới',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 24),
            
            // Hiển thị thông tin bệnh nhân đang đăng nhập
            _buildSectionTitle('Thông tin bệnh nhân'),
            Card(
              elevation: 2,
              color: Colors.blue.shade50,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Icon(Icons.person, color: Colors.blue.shade700, size: 32),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _currentPatient != null
                                ? (_currentPatient!['personal_info']?['full_name'] ?? 'Bệnh nhân')
                                : 'Đang tải...',
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'ID: ${_currentPatientId ?? "---"}',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            // Thêm khoảng trống phía dưới để tránh bị che bởi navbar
            const SizedBox(height: 40),

            // Chọn bác sĩ
            _buildSectionTitle('Chọn bác sĩ'),
            Card(
              elevation: 2,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SizedBox(
                      width: double.infinity,
                      child: DropdownButtonFormField<String>(
                        isExpanded: true,
                        decoration: const InputDecoration(
                          labelText: 'Chọn bác sĩ *',
                          border: OutlineInputBorder(),
                          prefixIcon: Icon(Icons.medical_services),
                        ),
                        value: _selectedDoctorId,
                        items: _doctors.isEmpty
                            ? []
                            : _doctors.map((doctor) {
                                final personalInfo = doctor['personal_info'] ?? {};
                                final professionalInfo = doctor['professional_info'] ?? {};
                                final name = personalInfo['full_name'] ?? doctor['_id'];
                                final specialty = professionalInfo['specialty'] ?? '';
                                return DropdownMenuItem<String>(
                                  value: doctor['_id'],
                                  child: Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          '$name${specialty.isNotEmpty ? " - $specialty" : ""}',
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              }).toList(),
                        onChanged: (value) {
                          setState(() {
                            _selectedDoctorId = value;
                          });
                          // Load lịch của bác sĩ
                          if (value != null) {
                            _loadDoctorAppointments();
                          }
                        },
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Vui lòng chọn bác sĩ';
                          }
                          return null;
                        },
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Chọn bác sĩ để xem khung giờ trống',
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Chọn ngày
            _buildSectionTitle('Chọn ngày khám'),
            Card(
              elevation: 2,
              child: ListTile(
                leading: const Icon(Icons.calendar_today, color: Colors.blue),
                title: const Text('Ngày hẹn'),
                subtitle: Text(
                  DateFormat('dd/MM/yyyy').format(_selectedDate),
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                onTap: _selectDate,
              ),
            ),
            const SizedBox(height: 16),

            // Hiển thị khung giờ trống
            if (_selectedDoctorId != null) ...[
              _buildSectionTitle('Chọn khung giờ trống'),
              if (_isLoadingTimeSlots)
                const Center(
                  child: Padding(
                    padding: EdgeInsets.all(20),
                    child: CircularProgressIndicator(),
                  ),
                )
              else if (_availableTimeSlots.isEmpty)
                Card(
                  elevation: 2,
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Center(
                      child: Column(
                        children: [
                          Icon(Icons.event_busy, size: 48, color: Colors.grey.shade400),
                          const SizedBox(height: 12),
                          Text(
                            'Không có khung giờ trống trong ngày này',
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey.shade600,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Vui lòng chọn ngày khác',
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.grey.shade500,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                )
              else
                Card(
                  elevation: 2,
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.access_time, color: Colors.green.shade600),
                            const SizedBox(width: 8),
                            Text(
                              'Có ${_availableTimeSlots.length} khung giờ trống',
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.green.shade700,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: _availableTimeSlots.map((slot) {
                            final start = slot['start'] as DateTime;
                            final startTime = TimeOfDay.fromDateTime(start);
                            final isSelected = _selectedTime?.hour == startTime.hour &&
                                _selectedTime?.minute == startTime.minute;

                            return ChoiceChip(
                              label: Text(
                                DateFormat('HH:mm').format(start),
                              ),
                              selected: isSelected,
                              onSelected: (selected) {
                                if (selected) {
                                  setState(() {
                                    _selectedTime = startTime;
                                    // Tính toán lại duration khả dụng khi chọn giờ mới
                                    _calculateAvailableDurations();
                                  });
                                }
                              },
                              selectedColor: Colors.blue.shade100,
                              backgroundColor: Colors.grey.shade100,
                            );
                          }).toList(),
                        ),
                      ],
                    ),
                  ),
                ),
              const SizedBox(height: 16),

              // Thời lượng khám
              _buildSectionTitle('Thời lượng khám'),
              Card(
                elevation: 2,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: DropdownButtonFormField<int>(
                    decoration: const InputDecoration(
                      labelText: 'Thời lượng (phút) *',
                      border: OutlineInputBorder(),
                      prefixIcon: Icon(Icons.timer),
                    ),
                    value: _getValidDuration(),
                    items: _getDurationItems(),
                    onChanged: (value) {
                      if (value != null) {
                        setState(() {
                          _duration = value;
                        });
                      }
                    },
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Chi tiết cuộc hẹn
            _buildSectionTitle('Chi tiết cuộc hẹn'),
            Card(
              elevation: 2,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        labelText: 'Loại cuộc hẹn *',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.category),
                      ),
                      value: _appointmentType,
                      items: const [
                        DropdownMenuItem(value: 'consultation', child: Text('Tư vấn')),
                        DropdownMenuItem(value: 'follow_up', child: Text('Tái khám')),
                        DropdownMenuItem(value: 'checkup', child: Text('Khám sức khỏe')),
                        DropdownMenuItem(value: 'emergency', child: Text('Cấp cứu')),
                        DropdownMenuItem(value: 'procedure', child: Text('Thủ thuật')),
                      ],
                      onChanged: (value) {
                        if (value != null) {
                          setState(() {
                            _appointmentType = value;
                          });
                        }
                      },
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _reasonController,
                      decoration: const InputDecoration(
                        labelText: 'Lý do khám *',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.note),
                        hintText: 'Mô tả triệu chứng hoặc lý do khám',
                      ),
                      maxLines: 3,
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Vui lòng nhập lý do khám';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _notesController,
                      decoration: const InputDecoration(
                        labelText: 'Ghi chú (tùy chọn)',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.description),
                        hintText: 'Các thông tin bổ sung...',
                      ),
                      maxLines: 2,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Nút submit
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _submitAppointment,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue.shade600,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: _isSubmitting
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                        ),
                      )
                    : const Text(
                        'Tạo lịch hẹn',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: Colors.black87,
        ),
      ),
    );
  }
}
