import 'package:flutter/material.dart';
import 'medication_detail_screen.dart';

class TreatmentDetailScreen extends StatelessWidget {
  final Map<String, dynamic> treatment;
  final List<dynamic>? allMedications;

  const TreatmentDetailScreen({
    Key? key, 
    required this.treatment,
    this.allMedications,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final treatmentInfo = treatment['treatment_info'] ?? {};
    final medications = (treatment['medications'] as List?) ?? [];
    final monitoring = treatment['monitoring'] ?? {};
    final status = treatment['status'] ?? '';

    final treatmentName = treatmentInfo['treatment_name'] ?? '';
    final startDate = treatmentInfo['start_date'] ?? '';
    final endDate = treatmentInfo['end_date'] ?? '';
    final durationDays = treatmentInfo['duration_days'] ?? 0;
    final treatmentType = treatmentInfo['treatment_type'] ?? '';

    final parameters = (monitoring['parameters'] as List?)?.join(', ') ?? '';
    final frequency = monitoring['frequency'] ?? '';
    final nextCheck = monitoring['next_check'] ?? '';

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

    return Scaffold(
      appBar: AppBar(
        title: Text(treatmentName),
        backgroundColor: Colors.blue.shade600,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildSectionTitle('Thông tin điều trị'),
            Card(
              elevation: 2,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _buildInfoRow('Tên điều trị', treatmentName),
                    _buildInfoRow('Loại điều trị', treatmentType),
                    _buildInfoRow('Ngày bắt đầu', startDate),
                    _buildInfoRow('Ngày kết thúc', endDate),
                    _buildInfoRow('Thời gian', '$durationDays ngày'),
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
            _buildSectionTitle('Thuốc điều trị'),
            if (medications.isEmpty)
              Card(
                elevation: 2,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Center(
                    child: Text(
                      'Không có thuốc điều trị',
                      style: TextStyle(color: Colors.grey.shade600),
                    ),
                  ),
                ),
              )
            else
              ...medications.map((med) {
                final name = med['name'] ?? 'Tên thuốc';
                final dosage = med['dosage'] ?? '';
                final frequency = med['frequency'] ?? '';
                final route = med['route'] ?? '';
                final instructions = med['instructions'] ?? '';
                final quantity = med['quantity_prescribed'] ?? 0;

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
                          name,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 8),
                        _buildInfoRow('Liều lượng', dosage),
                        _buildInfoRow('Tần suất', frequency),
                        _buildInfoRow('Đường dùng', route),
                        _buildInfoRow('Số lượng', '$quantity'),
                        _buildInfoRow('Hướng dẫn', instructions),
                      ],
                    ),
                  ),
                );
              }).toList(),
            const SizedBox(height: 16),
            _buildSectionTitle('Thông tin thuốc'),
            if (allMedications == null || allMedications!.isEmpty)
              Card(
                elevation: 2,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Center(
                    child: Text(
                      'Không có thông tin thuốc',
                      style: TextStyle(color: Colors.grey.shade600),
                    ),
                  ),
                ),
              )
            else
              ...medications.map((med) {
                final medicationId = med['medication_id'];
                // Tìm medication detail từ allMedications
                final medicationDetail = allMedications!.firstWhere(
                  (m) => m['_id'] == medicationId,
                  orElse: () => null,
                );
                
                if (medicationDetail == null) {
                  return const SizedBox.shrink();
                }
                
                final medInfo = medicationDetail['medication_info'] ?? {};
                final clinicalInfo = medicationDetail['clinical_info'] ?? {};
                final inventory = medicationDetail['inventory'] ?? {};
                
                final name = medInfo['name'] ?? 'Tên thuốc';
                final genericName = medInfo['generic_name'] ?? '';
                final strength = medInfo['strength'] ?? '';
                final dosageForm = medInfo['dosage_form'] ?? '';
                final therapeuticClass = clinicalInfo['therapeutic_class'] ?? '';
                final currentStock = inventory['current_stock'] ?? 0;

                return Card(
                  elevation: 2,
                  margin: const EdgeInsets.only(bottom: 8),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: InkWell(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => MedicationDetailScreen(medication: medicationDetail),
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
                                  name,
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
                                  color: Colors.green.withOpacity(0.2),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  'Tồn: $currentStock',
                                  style: const TextStyle(
                                    color: Colors.green,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 12,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text(
                            genericName,
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 14,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              Icon(Icons.medical_services, size: 16, color: Colors.blue.shade400),
                              const SizedBox(width: 4),
                              Text(
                                '$strength - $dosageForm',
                                style: TextStyle(
                                  color: Colors.grey.shade700,
                                  fontSize: 14,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              Icon(Icons.category, size: 16, color: Colors.orange.shade400),
                              const SizedBox(width: 4),
                              Expanded(
                                child: Text(
                                  therapeuticClass,
                                  style: TextStyle(
                                    color: Colors.grey.shade700,
                                    fontSize: 14,
                                  ),
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
            _buildSectionTitle('Theo dõi'),
            Card(
              elevation: 2,
              color: Colors.blue.shade50,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildInfoRow('Chỉ số theo dõi', parameters),
                    _buildInfoRow('Tần suất', frequency),
                    _buildInfoRow('Kiểm tra tiếp theo', nextCheck),
                  ],
                ),
              ),
            ),
          ],
        ),
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
}
