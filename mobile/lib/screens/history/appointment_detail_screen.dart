import 'package:flutter/material.dart';
import '../../api/medical_record_service.dart';
import '../../api/invoice_service.dart';
import '../../api/doctor_service.dart';

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
  Map<String, dynamic>? doctor;
  bool isLoading = true;
  String? errorMessage;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadData();
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
      final recordsResult = await MedicalRecordService.getMedicalRecords();
      if (recordsResult['success'] == true) {
        allMedicalRecords = recordsResult['data']['data'] ?? [];
      }

      // Load tất cả invoices
      final invoicesResult = await InvoiceService.getInvoices();
      if (invoicesResult['success'] == true) {
        allInvoices = invoicesResult['data']['data'] ?? [];
      }

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
    try {
      return allMedicalRecords.firstWhere(
        (record) => record['appointment_id'] == widget.appointment['_id'],
        orElse: () => null,
      );
    } catch (e) {
      return null;
    }
  }

  // Lấy invoice của medical record
  Map<String, dynamic>? _getInvoice(String? medicalRecordId) {
    if (medicalRecordId == null) return null;
    try {
      return allInvoices.firstWhere(
        (invoice) => invoice['medical_record_id'] == medicalRecordId,
        orElse: () => null,
      );
    } catch (e) {
      return null;
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
            Tab(text: 'Hóa đơn', icon: Icon(Icons.receipt_long_outlined)),
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
                _buildInvoiceTab(),
              ],
            ),
    );
  }

  // Tab 1: Thông tin appointment
  Widget _buildAppointmentTab() {
    final apt = widget.appointment;
    final scheduledDate = apt['scheduled_date'] ?? '';
    final duration = apt['duration'] ?? 0;
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
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
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
                        _buildInfoRow('Tên bác sĩ', doctor!['full_name'] ?? ''),
                        _buildInfoRow(
                          'Chuyên khoa',
                          doctor!['specialty'] ?? '',
                        ),
                        _buildInfoRow('Số điện thoại', doctor!['phone'] ?? ''),
                        _buildInfoRow('Email', doctor!['email'] ?? ''),
                      ],
                    )
                  : const Center(
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
    final treatment = medicalRecord['treatment'] ?? {};
    final prescriptions = medicalRecord['prescriptions'] as List? ?? [];

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
                children: [
                  _buildInfoRow(
                    'Chẩn đoán chính',
                    diagnosis['primary'] ?? 'Chưa có',
                  ),
                  _buildInfoRow(
                    'Chẩn đoán phụ',
                    diagnosis['secondary']?.join(', ') ?? 'Không có',
                  ),
                  _buildInfoRow(
                    'Mã ICD-10',
                    diagnosis['icd_code'] ?? 'Chưa có',
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _buildSectionTitle('Điều trị'),
          Card(
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  _buildInfoRow(
                    'Kế hoạch',
                    treatment['plan'] ?? 'Chưa có kế hoạch',
                  ),
                  _buildInfoRow('Ghi chú', treatment['notes'] ?? 'Không có'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _buildSectionTitle('Đơn thuốc'),
          if (prescriptions.isEmpty)
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
            ...prescriptions.map((prescription) {
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
                        prescription['medication_name'] ?? 'Tên thuốc',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 8),
                      _buildInfoRow('Liều lượng', prescription['dosage'] ?? ''),
                      _buildInfoRow(
                        'Tần suất',
                        prescription['frequency'] ?? '',
                      ),
                      _buildInfoRow(
                        'Thời gian',
                        prescription['duration'] ?? '',
                      ),
                      if (prescription['instructions'] != null)
                        _buildInfoRow(
                          'Hướng dẫn',
                          prescription['instructions'],
                        ),
                    ],
                  ),
                ),
              );
            }).toList(),
        ],
      ),
    );
  }

  // Tab 3: Hóa đơn
  Widget _buildInvoiceTab() {
    final medicalRecord = _getMedicalRecord();
    final invoice = _getInvoice(medicalRecord?['_id']);

    if (invoice == null) {
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

    final items = invoice['items'] as List? ?? [];
    final totalAmount = invoice['total_amount'] ?? 0;
    final paidAmount = invoice['paid_amount'] ?? 0;
    final status = invoice['status'] ?? '';
    final issueDate = invoice['issue_date'] ?? '';

    String statusText;
    Color statusColor;
    switch (status) {
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

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionTitle('Thông tin hóa đơn'),
          Card(
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  _buildInfoRow('Ngày phát hành', issueDate),
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
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
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
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _buildSectionTitle('Chi tiết dịch vụ'),
          ...items.map((item) {
            return Card(
              elevation: 1,
              margin: const EdgeInsets.only(bottom: 8),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item['description'] ?? '',
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Số lượng: ${item['quantity'] ?? 1}',
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Text(
                      '${_formatCurrency(item['amount'] ?? 0)}',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                        color: Colors.blue,
                      ),
                    ),
                  ],
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
                        'Tổng tiền:',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        _formatCurrency(totalAmount),
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
                        _formatCurrency(paidAmount),
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
                        _formatCurrency(totalAmount - paidAmount),
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: (totalAmount - paidAmount) > 0
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
