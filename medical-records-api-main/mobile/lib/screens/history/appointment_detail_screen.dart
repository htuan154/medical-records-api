import 'package:flutter/material.dart';
import '../../api/medical_record_service.dart';
import '../../api/invoice_service.dart';
import '../../api/doctor_service.dart';
import '../../api/token_service.dart';
import '../../api/treatment_service.dart';
import '../../api/medication_service.dart';
import '../../api/medical_test_service.dart';
import 'invoice_detail_screen.dart';
import 'treatment_detail_screen.dart';
import 'medication_detail_screen.dart';
import 'medical_test_detail_screen.dart';

class AppointmentDetailScreen extends StatefulWidget {
  final Map<String, dynamic> appointment;

  const AppointmentDetailScreen({Key? key, required this.appointment})
    : super(key: key);

  @override
  State<AppointmentDetailScreen> createState() =>
      _AppointmentDetailScreenState();
}

class _AppointmentDetailScreenState extends State<AppointmentDetailScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> allMedicalRecords = [];
  List<dynamic> allInvoices = [];
  List<dynamic> allTreatments = [];
  List<dynamic> allMedications = [];
  List<dynamic> allMedicalTests = [];
  bool invoicesLoaded = false;
  bool treatmentsLoaded = false;
  bool medicationsLoaded = false;
  bool medicalTestsLoaded = false;
  Map<String, dynamic>? doctor;
  bool isLoading = true;
  String? errorMessage;


  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);
    _loadData();
  }

  @override
  void didUpdateWidget(AppointmentDetailScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    // If the number of tabs changes, re-initialize the controller
    if (_tabController.length != 5) {
      _tabController.dispose();
      _tabController = TabController(length: 5, vsync: this);
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() {
      isLoading = true;
      errorMessage = null;
    });

    try {
      // Load thông tin bác sĩ
      final doctorId = widget.appointment['doctor_id'];
      if (doctorId != null) {
        final doctorResult = await DoctorService.getDoctorById(doctorId);
        if (doctorResult['success'] == true) {
          doctor = doctorResult['data'];
        }
      }

      // Load tất cả medical records
      final token = await TokenService.getToken();
      print('[DEBUG] medicalRecords API token: $token');
      final recordsResult = await MedicalRecordService.getMedicalRecords();
      print('[DEBUG] medicalRecords API response: ${recordsResult.toString()}');
      if (recordsResult['data'] != null && recordsResult['data']['statusCode'] != null) {
        print('[DEBUG] medicalRecords API statusCode: ${recordsResult['data']['statusCode']}');
      }
      if (recordsResult['success'] == true) {
        allMedicalRecords = (recordsResult['data']['rows'] as List<dynamic>?)
          ?.map((row) => row['doc'])
          .toList() ?? [];
      }

      // Không load invoices và treatments ở đây nữa, chỉ load khi mở tab tương ứng
      allInvoices = [];
      allTreatments = [];
      allMedications = [];
      allMedicalTests = [];
      invoicesLoaded = false;
      treatmentsLoaded = false;
      medicationsLoaded = false;
      medicalTestsLoaded = false;

      setState(() {
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        errorMessage = 'Lỗi kết nối: $e';
        isLoading = false;
      });
    }
  }

  // Lấy medical record của appointment này
  Map<String, dynamic>? _getMedicalRecord() {
    // Debug: print all medical records and current appointment id
    print('[DEBUG] allMedicalRecords:');
    for (var record in allMedicalRecords) {
      print(record);
    }
    print('[DEBUG] current appointment_id: ${widget.appointment['_id']}');

    try {
      return allMedicalRecords.firstWhere(
        (record) =>
          (record['visit_info']?['appointment_id'] ?? record['appointment_id']) == widget.appointment['_id'],
        orElse: () => null,
      );
    } catch (e) {
      print('[DEBUG] error in _getMedicalRecord: $e');
      return null;
    }
  }

  // Lấy danh sách invoices của medical record
  List<Map<String, dynamic>> _getInvoices(String? medicalRecordId) {
    if (medicalRecordId == null) return [];
    try {
      return allInvoices
          .where((invoice) => invoice['medical_record_id'] == medicalRecordId)
          .cast<Map<String, dynamic>>()
          .toList();
    } catch (e) {
      print('[DEBUG] error in _getInvoices: $e');
      return [];
    }
  }

  // Lấy danh sách treatments của medical record
  List<Map<String, dynamic>> _getTreatments(String? medicalRecordId) {
    if (medicalRecordId == null) return [];
    try {
      return allTreatments
          .where((treatment) => treatment['medical_record_id'] == medicalRecordId)
          .cast<Map<String, dynamic>>()
          .toList();
    } catch (e) {
      print('[DEBUG] error in _getTreatments: $e');
      return [];
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Chi tiết lịch hẹn'),
        backgroundColor: Colors.blue.shade600,
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'Thông tin', icon: Icon(Icons.info_outline)),
            Tab(text: 'Bệnh án', icon: Icon(Icons.medical_services_outlined)),
            Tab(text: 'Điều trị', icon: Icon(Icons.healing_outlined)),
            Tab(text: 'Hóa đơn', icon: Icon(Icons.receipt_long_outlined)),
            Tab(text: 'Xét nghiệm', icon: Icon(Icons.science_outlined)),
          ],
        ),
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : errorMessage != null
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.error_outline,
                    size: 64,
                    color: Colors.red.shade300,
                  ),
                  const SizedBox(height: 16),
                  Text(errorMessage!, style: const TextStyle(fontSize: 16)),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _loadData,
                    child: const Text('Thử lại'),
                  ),
                ],
              ),
            )
          : TabBarView(
              controller: _tabController,
              children: [
                _buildAppointmentTab(),
                _buildMedicalRecordTab(),
                Builder(
                  builder: (context) {
                    // Khi tab 3 được build, load treatments nếu chưa load
                    if (!treatmentsLoaded) {
                      _loadTreatmentsForMedicalRecords();
                    }
                    return _buildTreatmentTab();
                  },
                ),
                Builder(
                  builder: (context) {
                    // Khi tab 4 được build, load invoices nếu chưa load
                    if (!invoicesLoaded) {
                      _loadInvoicesForMedicalRecords();
                    }
                    return _buildInvoiceTab();
                  },
                ),
                Builder(
                  builder: (context) {
                    // Khi tab 5 được build, load medical tests nếu chưa load
                    if (!medicalTestsLoaded) {
                      _loadMedicalTestsForMedicalRecords();
                    }
                    return _buildMedicalTestTab();
                  },
                ),
              ],
            ),
    );

  }

  Future<void> _loadInvoicesForMedicalRecords() async {
    invoicesLoaded = true;
    // Lấy danh sách medical_record_id từ allMedicalRecords
    final ids = allMedicalRecords.map((r) => r['_id']).toList();
    if (ids.isEmpty) {
      setState(() {
        allInvoices = [];
      });
      return;
    }
    final token = await TokenService.getToken();
    print('[DEBUG] invoice API token: $token');
    // Gọi API lấy tất cả hóa đơn (không filter trên server, filter ở client)
    final invoicesResult = await InvoiceService.getInvoices();
    print('[DEBUG] invoice API response: ${invoicesResult.toString()}');
    List<dynamic> invoices = [];
    if (invoicesResult['success'] == true) {
      final rows = invoicesResult['data']['rows'] as List<dynamic>?;
      if (rows != null) {
        invoices = rows.map((row) => row['doc']).where((doc) => ids.contains(doc['medical_record_id'])).toList();
      }
    }
    setState(() {
      allInvoices = invoices;
    });
  }

  Future<void> _loadTreatmentsForMedicalRecords() async {
    treatmentsLoaded = true;
    // Lấy danh sách medical_record_id từ allMedicalRecords
    final ids = allMedicalRecords.map((r) => r['_id']).toList();
    if (ids.isEmpty) {
      setState(() {
        allTreatments = [];
      });
      return;
    }
    final token = await TokenService.getToken();
    print('[DEBUG] treatment API token: $token');
    // Gọi API lấy tất cả treatments (không filter trên server, filter ở client)
    final treatmentsResult = await TreatmentService.getTreatments();
    print('[DEBUG] treatment API response: ${treatmentsResult.toString()}');
    List<dynamic> treatments = [];
    if (treatmentsResult['success'] == true) {
      final rows = treatmentsResult['data']['rows'] as List<dynamic>?;
      if (rows != null) {
        treatments = rows.map((row) => row['doc']).where((doc) => ids.contains(doc['medical_record_id'])).toList();
      }
    }
    setState(() {
      allTreatments = treatments;
    });
    
    // Load medications sau khi có treatments
    _loadMedicationsForTreatments();
  }

  Future<void> _loadMedicationsForTreatments() async {
    if (medicationsLoaded) return;
    medicationsLoaded = true;
    
    // Lấy danh sách medication_id từ tất cả treatments
    Set<String> medicationIds = {};
    for (var treatment in allTreatments) {
      final medications = (treatment['medications'] as List?) ?? [];
      for (var med in medications) {
        final medId = med['medication_id'];
        if (medId != null) {
          medicationIds.add(medId);
        }
      }
    }
    
    if (medicationIds.isEmpty) {
      setState(() {
        allMedications = [];
      });
      return;
    }
    
    final token = await TokenService.getToken();
    print('[DEBUG] medication API token: $token');
    // Gọi API lấy tất cả medications
    final medicationsResult = await MedicationService.getMedications();
    print('[DEBUG] medication API response: ${medicationsResult.toString()}');
    List<dynamic> medications = [];
    if (medicationsResult['success'] == true) {
      final rows = medicationsResult['data']['rows'] as List<dynamic>?;
      if (rows != null) {
        medications = rows.map((row) => row['doc']).where((doc) => medicationIds.contains(doc['_id'])).toList();
      }
    }
    setState(() {
      allMedications = medications;
    });
  }

  Future<void> _loadMedicalTestsForMedicalRecords() async {
    if (medicalTestsLoaded) return;
    medicalTestsLoaded = true;
    
    // Lấy danh sách medical_record_id từ allMedicalRecords
    final ids = allMedicalRecords.map((r) => r['_id']).toList();
    if (ids.isEmpty) {
      setState(() {
        allMedicalTests = [];
      });
      return;
    }
    
    final token = await TokenService.getToken();
    print('[DEBUG] medical_test API token: $token');
    // Gọi API lấy tất cả medical tests
    final testsResult = await MedicalTestService.getMedicalTests();
    print('[DEBUG] medical_test API response: ${testsResult.toString()}');
    List<dynamic> tests = [];
    if (testsResult['success'] == true) {
      final rows = testsResult['data']['rows'] as List<dynamic>?;
      if (rows != null) {
        tests = rows.map((row) => row['doc']).where((doc) => ids.contains(doc['medical_record_id'])).toList();
      }
    }
    setState(() {
      allMedicalTests = tests;
    });
  }

  // Lấy danh sách medical tests của medical record
  List<Map<String, dynamic>> _getMedicalTests(String? medicalRecordId) {
    if (medicalRecordId == null) return [];
    try {
      return allMedicalTests
          .where((test) => test['medical_record_id'] == medicalRecordId)
          .cast<Map<String, dynamic>>()
          .toList();
    } catch (e) {
      print('[DEBUG] error in _getMedicalTests: $e');
      return [];
    }
  }

  // Tab 1: Thông tin appointment
  Widget _buildAppointmentTab() {
  final apt = widget.appointment;
  final appointmentInfo = apt['appointment_info'] ?? {};
  final scheduledDate = appointmentInfo['scheduled_date'] ?? '';
  final duration = appointmentInfo['duration'] ?? 0;
  final status = apt['status'] ?? '';
  final reason = apt['reason'] ?? 'Không có ghi chú';
  final notes = apt['notes'] ?? '';

    String statusText;
    Color statusColor;
    switch (status) {
      case 'scheduled':
        statusText = 'Đã đặt';
        statusColor = Colors.blue;
        break;
      case 'completed':
        statusText = 'Hoàn thành';
        statusColor = Colors.green;
        break;
      case 'cancelled':
        statusText = 'Đã hủy';
        statusColor = Colors.red;
        break;
      default:
        statusText = 'Không rõ';
        statusColor = Colors.grey;
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionTitle('Thông tin cuộc hẹn'),
          Card(
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  _buildInfoRow('Ngày hẹn', scheduledDate),
                  _buildInfoRow('Thời gian', '$duration phút'),
                  Row(
                    children: [
                      const SizedBox(
                        width: 120,
                        child: Text(
                          'Trạng thái',
                          style: TextStyle(color: Colors.grey),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: statusColor.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          statusText,
                          style: TextStyle(
                            color: statusColor,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                  _buildInfoRow('Lý do khám', reason),
                  if (notes.isNotEmpty) _buildInfoRow('Ghi chú', notes),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _buildSectionTitle('Thông tin bác sĩ'),
          Card(
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: doctor != null
                  ? Column(
                      children: [
                        _buildInfoRow('Tên bác sĩ', doctor!['personal_info']?['full_name'] ?? ''),
                        _buildInfoRow('Chuyên khoa', doctor!['professional_info']?['specialty'] ?? ''),
                        _buildInfoRow('Số điện thoại', doctor!['personal_info']?['phone'] ?? ''),
                        _buildInfoRow('Email', doctor!['personal_info']?['email'] ?? ''),
                      ],
                    )
                  : Center(
                      child: Text('Không tìm thấy thông tin bác sĩ'),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  // Tab 2: Bệnh án
  Widget _buildMedicalRecordTab() {
    final medicalRecord = _getMedicalRecord();

    if (medicalRecord == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.medical_services_outlined,
              size: 64,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            const Text(
              'Chưa có bệnh án cho cuộc hẹn này',
              style: TextStyle(fontSize: 16, color: Colors.grey),
            ),
          ],
        ),
      );
    }

    final diagnosis = medicalRecord['diagnosis'] ?? {};
    final examination = medicalRecord['examination'] ?? {};
    final treatmentPlan = medicalRecord['treatment_plan'] ?? {};
    final attachments = (medicalRecord['attachments'] as List?) ?? [];

    // Chẩn đoán
    String diagnosisPrimaryText = 'Chưa có';
    if (diagnosis['primary'] != null && diagnosis['primary'] is Map) {
      final primary = diagnosis['primary'];
      diagnosisPrimaryText = '${primary['description'] ?? ''} (${primary['code'] ?? ''})';
      if (primary['severity'] != null && primary['severity'].toString().isNotEmpty) {
        diagnosisPrimaryText += ' - ${primary['severity']}';
      }
    }
    final secondaryDiagnosis = (diagnosis['secondary'] as List?)?.join(', ') ?? 'Không có';
    final differentialDiagnosis = (diagnosis['differential'] as List?)?.join(', ') ?? 'Không có';

    // Khám lâm sàng
    final vitalSigns = examination['vital_signs'] ?? {};
    final physicalExam = examination['physical_exam'] ?? {};

    // Điều trị
    final medications = (treatmentPlan['medications'] as List?) ?? [];
    final procedures = (treatmentPlan['procedures'] as List?) ?? [];
    final lifestyleAdvice = (treatmentPlan['lifestyle_advice'] as List?) ?? [];
    final followUp = treatmentPlan['follow_up'] ?? {};

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionTitle('Chẩn đoán'),
          Card(
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildInfoRow('Chẩn đoán chính', diagnosisPrimaryText),
                  _buildInfoRow('Chẩn đoán phụ', secondaryDiagnosis),
                  _buildInfoRow('Chẩn đoán phân biệt', differentialDiagnosis),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _buildSectionTitle('Khám lâm sàng'),
          Card(
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildInfoRow('Nhiệt độ', vitalSigns['temperature']?.toString() ?? ''),
                  _buildInfoRow('Huyết áp', vitalSigns['blood_pressure'] != null ? '${vitalSigns['blood_pressure']['systolic']}/${vitalSigns['blood_pressure']['diastolic']} mmHg' : ''),
                  _buildInfoRow('Mạch', vitalSigns['heart_rate']?.toString() ?? ''),
                  _buildInfoRow('Nhịp thở', vitalSigns['respiratory_rate']?.toString() ?? ''),
                  _buildInfoRow('Cân nặng', vitalSigns['weight']?.toString() ?? ''),
                  _buildInfoRow('Chiều cao', vitalSigns['height']?.toString() ?? ''),
                  _buildInfoRow('Khám toàn thân', physicalExam['general'] ?? ''),
                  _buildInfoRow('Tim mạch', physicalExam['cardiovascular'] ?? ''),
                  _buildInfoRow('Hô hấp', physicalExam['respiratory'] ?? ''),
                  _buildInfoRow('Khác', physicalExam['other_findings'] ?? ''),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _buildSectionTitle('Kế hoạch điều trị'),
          Card(
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildInfoRow('Thủ thuật', procedures.isNotEmpty ? procedures.map((e) => e.toString()).join(', ') : 'Không có'),
                  _buildInfoRow('Lời khuyên lối sống', lifestyleAdvice.isNotEmpty ? lifestyleAdvice.join(', ') : 'Không có'),
                  _buildInfoRow('Tái khám', followUp['date'] ?? ''),
                  _buildInfoRow('Ghi chú tái khám', followUp['notes'] ?? ''),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _buildSectionTitle('Đơn thuốc'),
          if (medications.isEmpty)
            Card(
              elevation: 2,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Center(
                  child: Text(
                    'Không có đơn thuốc',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                ),
              ),
            )
          else
            ...medications.map((med) {
              return Card(
                elevation: 2,
                margin: const EdgeInsets.only(bottom: 8),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        med['name'] ?? 'Tên thuốc',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 8),
                      _buildInfoRow('Liều lượng', med['dosage'] ?? ''),
                      _buildInfoRow('Tần suất', med['frequency'] ?? ''),
                      _buildInfoRow('Thời gian', med['duration'] ?? ''),
                      _buildInfoRow('Hướng dẫn', med['instructions'] ?? ''),
                    ],
                  ),
                ),
              );
            }).toList(),
          const SizedBox(height: 16),
          _buildSectionTitle('Tệp đính kèm'),
          if (attachments.isEmpty)
            Card(
              elevation: 2,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Center(
                  child: Text(
                    'Không có tệp đính kèm',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                ),
              ),
            )
          else
            ...attachments.map((att) {
              return Card(
                elevation: 1,
                margin: const EdgeInsets.only(bottom: 8),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(Icons.attach_file, color: Colors.blue.shade400),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(att['file_name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                            Text(att['description'] ?? '', style: TextStyle(color: Colors.grey.shade600)),
                          ],
                        ),
                      ),
                      Text(att['type'] ?? '', style: TextStyle(color: Colors.grey.shade600)),
                    ],
                  ),
                ),
              );
            }).toList(),
        ],
      ),
    );
  }

  // Tab 3: Điều trị
  Widget _buildTreatmentTab() {
    final medicalRecord = _getMedicalRecord();
    print('[DEBUG] _buildTreatmentTab: allTreatments =');
    for (var treat in allTreatments) {
      print(treat);
    }
    print('[DEBUG] _buildTreatmentTab: medicalRecord = $medicalRecord');
    final treatments = _getTreatments(medicalRecord?['_id']);
    print('[DEBUG] _buildTreatmentTab: selected treatments count = ${treatments.length}');

    if (treatments.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.healing_outlined,
              size: 64,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            const Text(
              'Chưa có điều trị cho cuộc hẹn này',
              style: TextStyle(fontSize: 16, color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionTitle('Danh sách điều trị'),
          ...treatments.map((treatment) {
            final treatmentInfo = treatment['treatment_info'] ?? {};
            final status = treatment['status'] ?? '';
            final treatmentName = treatmentInfo['treatment_name'] ?? 'Điều trị';
            final startDate = treatmentInfo['start_date'] ?? '';
            final endDate = treatmentInfo['end_date'] ?? '';
            final treatmentType = treatmentInfo['treatment_type'] ?? '';
            final medications = (treatment['medications'] as List?) ?? [];

            String statusText;
            Color statusColor;
            switch (status) {
              case 'active':
                statusText = 'Đang điều trị';
                statusColor = Colors.green;
                break;
              case 'completed':
                statusText = 'Hoàn thành';
                statusColor = Colors.blue;
                break;
              case 'cancelled':
                statusText = 'Đã hủy';
                statusColor = Colors.red;
                break;
              default:
                statusText = 'Không rõ';
                statusColor = Colors.grey;
            }

            return Card(
              elevation: 2,
              margin: const EdgeInsets.only(bottom: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: InkWell(
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => TreatmentDetailScreen(
                        treatment: treatment,
                        allMedications: allMedications,
                      ),
                    ),
                  );
                },
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
                              treatmentName,
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 16,
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
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.category, size: 16, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Text(
                            'Loại: $treatmentType',
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.calendar_today, size: 16, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Text(
                            'Từ $startDate đến $endDate',
                            style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.medication, size: 16, color: Colors.blue.shade400),
                          const SizedBox(width: 4),
                          Text(
                            '${medications.length} loại thuốc',
                            style: TextStyle(
                              color: Colors.blue.shade700,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            );
          }).toList(),
        ],
      ),
    );
  }

  // Tab 4: Hóa đơn
  Widget _buildInvoiceTab() {
    final medicalRecord = _getMedicalRecord();
    print('[DEBUG] _buildInvoiceTab: allInvoices =');
    for (var inv in allInvoices) {
      print(inv);
    }
    print('[DEBUG] _buildInvoiceTab: medicalRecord = $medicalRecord');
    final invoices = _getInvoices(medicalRecord?['_id']);
    print('[DEBUG] _buildInvoiceTab: selected invoices count = ${invoices.length}');

    if (invoices.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.receipt_long_outlined,
              size: 64,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            const Text(
              'Chưa có hóa đơn cho cuộc hẹn này',
              style: TextStyle(fontSize: 16, color: Colors.grey),
            ),
          ],
        ),
      );
    }

    // Tính tổng tiền từ tất cả invoices
    num totalAllInvoices = 0;
    num paidAllInvoices = 0;
    for (var invoice in invoices) {
      final paymentInfo = invoice['payment_info'] ?? {};
      totalAllInvoices += (paymentInfo['total_amount'] ?? 0);
      final paymentStatus = invoice['payment_status'] ?? '';
      if (paymentStatus == 'paid') {
        paidAllInvoices += (paymentInfo['total_amount'] ?? 0);
      }
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionTitle('Danh sách hóa đơn'),
          ...invoices.map((invoice) {
            final invoiceInfo = invoice['invoice_info'] ?? {};
            final paymentInfo = invoice['payment_info'] ?? {};
            final paymentStatus = invoice['payment_status'] ?? '';
            final invoiceDate = invoiceInfo['invoice_date'] ?? '';
            final totalAmount = paymentInfo['total_amount'] ?? 0;

            String statusText;
            Color statusColor;
            switch (paymentStatus) {
              case 'paid':
                statusText = 'Đã thanh toán';
                statusColor = Colors.green;
                break;
              case 'pending':
                statusText = 'Chờ thanh toán';
                statusColor = Colors.orange;
                break;
              case 'cancelled':
                statusText = 'Đã hủy';
                statusColor = Colors.red;
                break;
              default:
                statusText = 'Không rõ';
                statusColor = Colors.grey;
            }

            return Card(
              elevation: 2,
              margin: const EdgeInsets.only(bottom: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: InkWell(
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => InvoiceDetailScreen(invoice: invoice),
                    ),
                  );
                },
                borderRadius: BorderRadius.circular(12),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            invoiceInfo['invoice_number'] ?? 'Hóa đơn',
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
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
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.calendar_today, size: 16, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Text(
                            invoiceDate,
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Tổng tiền:',
                            style: TextStyle(
                              color: Colors.grey.shade700,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          Text(
                            _formatCurrency(totalAmount),
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 18,
                              color: Colors.blue,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            );
          }).toList(),
          const SizedBox(height: 16),
          _buildSectionTitle('Tổng kết'),
          Card(
            elevation: 2,
            color: Colors.blue.shade50,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Tổng tiền tất cả:',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        _formatCurrency(totalAllInvoices),
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.blue,
                        ),
                      ),
                    ],
                  ),
                  const Divider(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Đã thanh toán:'),
                      Text(
                        _formatCurrency(paidAllInvoices),
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Còn lại:'),
                      Text(
                        _formatCurrency(totalAllInvoices - paidAllInvoices),
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: (totalAllInvoices - paidAllInvoices) > 0
                              ? Colors.red
                              : Colors.green,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // Tab 5: Xét nghiệm
  Widget _buildMedicalTestTab() {
    final medicalRecord = _getMedicalRecord();
    print('[DEBUG] _buildMedicalTestTab: allMedicalTests =');
    for (var test in allMedicalTests) {
      print(test);
    }
    print('[DEBUG] _buildMedicalTestTab: medicalRecord = $medicalRecord');
    final tests = _getMedicalTests(medicalRecord?['_id']);
    print('[DEBUG] _buildMedicalTestTab: selected tests count = ${tests.length}');

    if (tests.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.science_outlined,
              size: 64,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            const Text(
              'Chưa có xét nghiệm cho cuộc hẹn này',
              style: TextStyle(fontSize: 16, color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionTitle('Danh sách xét nghiệm'),
          ...tests.map((test) {
            final testInfo = test['test_info'] ?? {};
            final status = test['status'] ?? '';
            final testName = testInfo['test_name'] ?? 'Xét nghiệm';
            final testType = testInfo['test_type'] ?? '';
            final orderedDate = testInfo['ordered_date'] ?? '';
            final resultDate = testInfo['result_date'] ?? '';

            String statusText;
            Color statusColor;
            switch (status.toLowerCase()) {
              case 'pending':
                statusText = 'Đang chờ';
                statusColor = Colors.orange;
                break;
              case 'in_progress':
                statusText = 'Đang xử lý';
                statusColor = Colors.blue;
                break;
              case 'completed':
                statusText = 'Hoàn thành';
                statusColor = Colors.green;
                break;
              case 'cancelled':
                statusText = 'Đã hủy';
                statusColor = Colors.red;
                break;
              default:
                statusText = 'Không rõ';
                statusColor = Colors.grey;
            }

            return Card(
              elevation: 2,
              margin: const EdgeInsets.only(bottom: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: InkWell(
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => MedicalTestDetailScreen(test: test),
                    ),
                  );
                },
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
                              testName,
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 16,
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
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.biotech, size: 16, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Text(
                            'Loại: $testType',
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.calendar_today, size: 16, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Text(
                            'Chỉ định: ${_formatDateShort(orderedDate)}',
                            style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                          ),
                        ],
                      ),
                      if (resultDate.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Row(
                            children: [
                              Icon(Icons.done_all, size: 16, color: Colors.green.shade400),
                              const SizedBox(width: 4),
                              Text(
                                'Kết quả: ${_formatDateShort(resultDate)}',
                                style: TextStyle(
                                  color: Colors.green.shade700,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            );
          }).toList(),
        ],
      ),
    );
  }

  String _formatDateShort(String? date) {
    if (date == null || date.isEmpty) return 'N/A';
    try {
      final dt = DateTime.parse(date);
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
    } catch (e) {
      return date;
    }
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12, top: 8),
      child: Text(
        title,
        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
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

  String _formatCurrency(num amount) {
    return '${amount.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]},')} VNĐ';
  }
}
