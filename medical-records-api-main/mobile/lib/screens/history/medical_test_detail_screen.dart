import 'package:flutter/material.dart';

class MedicalTestDetailScreen extends StatelessWidget {
  final Map<String, dynamic> test;

  const MedicalTestDetailScreen({Key? key, required this.test})
      : super(key: key);

  @override
  Widget build(BuildContext context) {
    final testInfo = test['test_info'] ?? {};
    final results = test['results'] ?? {};
    final status = test['status'] ?? '';
    final interpretation = test['interpretation'] ?? '';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Chi tiết xét nghiệm'),
        backgroundColor: Colors.blue.shade600,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Thông tin chung
            _buildSectionCard(
              title: 'Thông tin xét nghiệm',
              children: [
                _buildInfoRow('Tên xét nghiệm', testInfo['test_name'] ?? 'N/A'),
                _buildInfoRow('Loại xét nghiệm', testInfo['test_type'] ?? 'N/A'),
                _buildInfoRow(
                  'Ngày chỉ định',
                  _formatDate(testInfo['ordered_date']),
                ),
                _buildInfoRow(
                  'Ngày lấy mẫu',
                  _formatDate(testInfo['sample_collected_date']),
                ),
                _buildInfoRow(
                  'Ngày có kết quả',
                  _formatDate(testInfo['result_date']),
                ),
                _buildInfoRow('Trạng thái', _getStatusText(status),
                    color: _getStatusColor(status)),
              ],
            ),
            const SizedBox(height: 16),

            // Kết quả xét nghiệm
            if (results.isNotEmpty)
              _buildSectionCard(
                title: 'Kết quả xét nghiệm',
                children: results.entries.map<Widget>((entry) {
                  final result = entry.value as Map<String, dynamic>;
                  return _buildResultRow(
                    entry.key.toUpperCase(),
                    result['value']?.toString() ?? 'N/A',
                    result['unit'] ?? '',
                    result['reference_range'] ?? '',
                    result['status'] ?? '',
                  );
                }).toList(),
              ),
            const SizedBox(height: 16),

            // Nhận xét
            if (interpretation.isNotEmpty)
              _buildSectionCard(
                title: 'Nhận xét',
                children: [
                  Text(
                    interpretation,
                    style: const TextStyle(fontSize: 15, height: 1.5),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionCard({
    required String title,
    required List<Widget> children,
  }) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF0F172A),
              ),
            ),
            const Divider(height: 24),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value, {Color? color}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(
              label,
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: Colors.grey.shade700,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                color: color ?? Colors.black87,
                fontWeight: color != null ? FontWeight.w600 : FontWeight.normal,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildResultRow(
    String name,
    String value,
    String unit,
    String referenceRange,
    String status,
  ) {
    Color statusColor;
    switch (status.toLowerCase()) {
      case 'normal':
        statusColor = Colors.green;
        break;
      case 'high':
      case 'low':
        statusColor = Colors.orange;
        break;
      case 'critical':
        statusColor = Colors.red;
        break;
      default:
        statusColor = Colors.grey;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: statusColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: statusColor.withOpacity(0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                name,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 15,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  status.toUpperCase(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Text(
                '$value $unit',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: statusColor,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Giá trị bình thường: $referenceRange',
            style: TextStyle(
              fontSize: 13,
              color: Colors.grey.shade600,
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(dynamic date) {
    if (date == null || date.toString().isEmpty) return 'N/A';
    try {
      final dt = DateTime.parse(date.toString());
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year} ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (e) {
      return date.toString();
    }
  }

  String _getStatusText(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return 'Đang chờ';
      case 'in_progress':
        return 'Đang xử lý';
      case 'completed':
        return 'Hoàn thành';
      case 'cancelled':
        return 'Đã hủy';
      default:
        return status;
    }
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return Colors.orange;
      case 'in_progress':
        return Colors.blue;
      case 'completed':
        return Colors.green;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}
