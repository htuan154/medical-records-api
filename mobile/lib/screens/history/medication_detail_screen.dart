import 'package:flutter/material.dart';

class MedicationDetailScreen extends StatelessWidget {
  final Map<String, dynamic> medication;

  const MedicationDetailScreen({Key? key, required this.medication}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final medInfo = medication['medication_info'] ?? {};
    final clinicalInfo = medication['clinical_info'] ?? {};
    final inventory = medication['inventory'] ?? {};
    final status = medication['status'] ?? '';

    final name = medInfo['name'] ?? '';
    final genericName = medInfo['generic_name'] ?? '';
    final strength = medInfo['strength'] ?? '';
    final dosageForm = medInfo['dosage_form'] ?? '';
    final manufacturer = medInfo['manufacturer'] ?? '';
    final barcode = medInfo['barcode'] ?? '';

    final therapeuticClass = clinicalInfo['therapeutic_class'] ?? '';
    final indications = (clinicalInfo['indications'] as List?)?.join(', ') ?? '';
    final contraindications = (clinicalInfo['contraindications'] as List?)?.join(', ') ?? '';
    final sideEffects = (clinicalInfo['side_effects'] as List?)?.join(', ') ?? '';
    final drugInteractions = (clinicalInfo['drug_interactions'] as List?)?.join(', ') ?? '';

    final currentStock = inventory['current_stock'] ?? 0;
    final unitCost = inventory['unit_cost'] ?? 0;
    final expiryDate = inventory['expiry_date'] ?? '';
    final supplier = inventory['supplier'] ?? '';

    String statusText;
    Color statusColor;
    switch (status) {
      case 'active':
        statusText = 'Đang hoạt động';
        statusColor = Colors.green;
        break;
      case 'discontinued':
        statusText = 'Ngưng sản xuất';
        statusColor = Colors.red;
        break;
      default:
        statusText = 'Không rõ';
        statusColor = Colors.grey;
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(name),
        backgroundColor: Colors.blue.shade600,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildSectionTitle('Thông tin thuốc'),
            Card(
              elevation: 2,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _buildInfoRow('Tên thuốc', name),
                    _buildInfoRow('Tên chung', genericName),
                    _buildInfoRow('Hàm lượng', strength),
                    _buildInfoRow('Dạng bào chế', dosageForm),
                    _buildInfoRow('Nhà sản xuất', manufacturer),
                    _buildInfoRow('Mã vạch', barcode),
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
            _buildSectionTitle('Thông tin lâm sàng'),
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
                    _buildInfoRow('Nhóm điều trị', therapeuticClass),
                    _buildInfoRow('Chỉ định', indications),
                    _buildInfoRow('Chống chỉ định', contraindications),
                    _buildInfoRow('Tác dụng phụ', sideEffects),
                    _buildInfoRow('Tương tác thuốc', drugInteractions),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            _buildSectionTitle('Kho'),
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
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Tồn kho:',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          '$currentStock',
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.blue,
                          ),
                        ),
                      ],
                    ),
                    const Divider(height: 24),
                    _buildInfoRow('Đơn giá', _formatCurrency(unitCost)),
                    _buildInfoRow('Hạn sử dụng', expiryDate),
                    _buildInfoRow('Nhà cung cấp', supplier),
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

  String _formatCurrency(num amount) {
    return '${amount.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]},')} VNĐ';
  }
}
