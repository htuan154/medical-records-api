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
      if (user == null || user['linked_patient_id'] == null) {
        _showError('Không tìm thấy thông tin bệnh nhân liên kết với tài khoản này');
        setState(() {
          _isLoading = false;
        });
        return;
      }

      _currentPatientId = user['linked_patient_id'];
      print('🔍 Current patient ID: $_currentPatientId');

      // 2. Lấy thông tin chi tiết bệnh nhân
      final userResult = await UserService.getUserById(user['_id']);
      if (userResult['success'] == true) {
        final userData = userResult['data'];
        if (userData != null && userData['linked_patient_id'] != null) {
          _currentPatient = {
            '_id': userData['linked_patient_id'],
            'personal_info': {
              'full_name': userData['username'] ?? 'Bệnh nhân',
            }
          };
        }
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
    } catch (e) {
      print('❌ Error loading data: $e');
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
        final scheduledDate = DateTime.parse(appointmentInfo['scheduled_date']);
        final duration = appointmentInfo['duration'] ?? 30;
        
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
    
    // Tạo khung giờ trống
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
    
    for (var busySlot in busySlots) {
      final busyStart = busySlot['start']!;
      final busyEnd = busySlot['end']!;
      
      // Thêm khoảng trống trước lịch hẹn này
      if (currentTime.isBefore(busyStart)) {
        // Kiểm tra không trùng giờ nghỉ
        DateTime slotEnd = busyStart;
        
        // Nếu khoảng trống chứa giờ nghỉ, chia thành 2 phần
        if (currentTime.isBefore(breakStartTime) && slotEnd.isAfter(breakStartTime)) {
          // Phần trước giờ nghỉ
          _availableTimeSlots.add({
            'start': currentTime,
            'end': breakStartTime,
          });
          // Phần sau giờ nghỉ
          if (breakEndTime.isBefore(slotEnd)) {
            currentTime = breakEndTime;
            continue;
          }
        } else if (currentTime.isBefore(breakStartTime) && slotEnd.isBefore(breakEndTime)) {
          // Khoảng trống hoàn toàn trước giờ nghỉ
          _availableTimeSlots.add({
            'start': currentTime,
            'end': slotEnd,
          });
        } else if (currentTime.isAfter(breakEndTime)) {
          // Khoảng trống sau giờ nghỉ
          _availableTimeSlots.add({
            'start': currentTime,
            'end': slotEnd,
          });
        }
      }
      
      currentTime = busyEnd;
    }
    
    // Thêm khoảng trống cuối cùng
    if (currentTime.isBefore(endTime)) {
      // Kiểm tra giờ nghỉ
      if (currentTime.isBefore(breakStartTime) && endTime.isAfter(breakStartTime)) {
        // Phần trước giờ nghỉ
        _availableTimeSlots.add({
          'start': currentTime,
          'end': breakStartTime,
        });
        // Phần sau giờ nghỉ
        _availableTimeSlots.add({
          'start': breakEndTime,
          'end': endTime,
        });
      } else if (currentTime.isAfter(breakEndTime)) {
        _availableTimeSlots.add({
          'start': currentTime,
          'end': endTime,
        });
      } else if (currentTime.isBefore(breakStartTime) && endTime.isBefore(breakStartTime)) {
        _availableTimeSlots.add({
          'start': currentTime,
          'end': endTime,
        });
      }
    }
    
    print('✅ Available time slots: $_availableTimeSlots');
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
      final scheduledDateTime = DateTime(
        _selectedDate.year,
        _selectedDate.month,
        _selectedDate.day,
        _selectedTime!.hour,
        _selectedTime!.minute,
      );

      final appointmentEndTime = scheduledDateTime.add(Duration(minutes: _duration));

      // Kiểm tra xung đột với các lịch hẹn khác
      bool hasConflict = false;
      for (var appointment in _doctorAppointments) {
        try {
          final appointmentInfo = appointment['appointment_info'];
          final existingStart = DateTime.parse(appointmentInfo['scheduled_date']);
          final existingDuration = appointmentInfo['duration'] ?? 30;
          final existingEnd = existingStart.add(Duration(minutes: existingDuration));

          // Kiểm tra xung đột: lịch mới bắt đầu trước khi lịch cũ kết thúc
          // và lịch mới kết thúc sau khi lịch cũ bắt đầu
          if (scheduledDateTime.isBefore(existingEnd) && 
              appointmentEndTime.isAfter(existingStart)) {
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

      // Kiểm tra thời gian kết thúc không vượt quá giờ làm việc (17h)
      final endOfDay = DateTime(
        _selectedDate.year,
        _selectedDate.month,
        _selectedDate.day,
        17,
        0,
      );

      if (appointmentEndTime.isAfter(endOfDay)) {
        _showError('Thời gian khám vượt quá giờ làm việc (17:00)!\n'
            'Vui lòng chọn khung giờ sớm hơn hoặc giảm thời gian khám.');
        setState(() {
          _isSubmitting = false;
        });
        return;
      }

      final appointmentData = {
        'type': 'appointment',
        'patient_id': _currentPatientId,
        'doctor_id': _selectedDoctorId,
        'appointment_info': {
          'scheduled_date': scheduledDateTime.toIso8601String(),
          'duration': _duration,
          'type': _appointmentType,
          'priority': 'normal', // Mặc định bình thường
        },
        'reason': _reasonController.text,
        'status': 'scheduled', // Mặc định đã đặt
        'notes': _notesController.text,
        'reminders': [],
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
      padding: const EdgeInsets.all(16),
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

            // Chọn bác sĩ
            _buildSectionTitle('Chọn bác sĩ'),
            Card(
              elevation: 2,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: DropdownButtonFormField<String>(
                  decoration: const InputDecoration(
                    labelText: 'Chọn bác sĩ *',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.medical_services),
                    helperText: 'Chọn bác sĩ để xem khung giờ trống',
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
                            child: Text('$name${specialty.isNotEmpty ? " - $specialty" : ""}'),
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
                            final end = slot['end'] as DateTime;
                            final startTime = TimeOfDay.fromDateTime(start);
                            final isSelected = _selectedTime?.hour == startTime.hour &&
                                _selectedTime?.minute == startTime.minute;

                            return ChoiceChip(
                              label: Text(
                                '${DateFormat('HH:mm').format(start)} - ${DateFormat('HH:mm').format(end)}',
                              ),
                              selected: isSelected,
                              onSelected: (selected) {
                                if (selected) {
                                  setState(() {
                                    _selectedTime = startTime;
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
                    value: _duration,
                    items: const [
                      DropdownMenuItem(value: 15, child: Text('15 phút')),
                      DropdownMenuItem(value: 30, child: Text('30 phút')),
                      DropdownMenuItem(value: 45, child: Text('45 phút')),
                      DropdownMenuItem(value: 60, child: Text('60 phút')),
                      DropdownMenuItem(value: 90, child: Text('90 phút')),
                      DropdownMenuItem(value: 120, child: Text('120 phút')),
                    ],
                    onChanged: (value) {
                      setState(() {
                        _duration = value!;
                      });
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
