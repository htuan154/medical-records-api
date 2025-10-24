import 'package:flutter/material.dart';

class InvoiceDetailScreen extends StatelessWidget {
  final Map<String, dynamic> invoice;

  const InvoiceDetailScreen({Key? key, required this.invoice}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final invoiceInfo = invoice['invoice_info'] ?? {};
    final paymentInfo = invoice['payment_info'] ?? {};
    final services = invoice['services'] as List? ?? [];
    final paymentStatus = invoice['payment_status'] ?? '';
    final paymentMethod = invoice['payment_method'] ?? '';

    final invoiceNumber = invoiceInfo['invoice_number'] ?? '';
    final invoiceDate = invoiceInfo['invoice_date'] ?? '';
    final dueDate = invoiceInfo['due_date'] ?? '';

    final subtotal = paymentInfo['subtotal'] ?? 0;
    final taxRate = paymentInfo['tax_rate'] ?? 0;
    final taxAmount = paymentInfo['tax_amount'] ?? 0;
    final totalAmount = paymentInfo['total_amount'] ?? 0;
    final insuranceCoverage = paymentInfo['insurance_coverage'] ?? 0;
    final insuranceAmount = paymentInfo['insurance_amount'] ?? 0;
    final patientPayment = paymentInfo['patient_payment'] ?? 0;

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

    String methodText;
    switch (paymentMethod) {
      case 'cash':
        methodText = 'Tiền mặt';
        break;
      case 'card':
        methodText = 'Thẻ';
        break;
      case 'transfer':
        methodText = 'Chuyển khoản';
        break;
      default:
        methodText = paymentMethod;
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(invoiceNumber),
        backgroundColor: Colors.blue.shade600,
      ),
      body: SingleChildScrollView(
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
                    _buildInfoRow('Số hóa đơn', invoiceNumber),
                    _buildInfoRow('Ngày phát hành', invoiceDate),
                    _buildInfoRow('Ngày đến hạn', dueDate),
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
                    _buildInfoRow('Phương thức', methodText),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            _buildSectionTitle('Chi tiết dịch vụ'),
            ...services.map((service) {
              final serviceType = service['service_type'] ?? '';
              final description = service['description'] ?? '';
              final quantity = service['quantity'] ?? 1;
              final unitPrice = service['unit_price'] ?? 0;
              final totalPrice = service['total_price'] ?? 0;

              return Card(
                elevation: 1,
                margin: const EdgeInsets.only(bottom: 8),
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
                          Expanded(
                            child: Text(
                              description,
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 16,
                              ),
                            ),
                          ),
                          Text(
                            _formatCurrency(totalPrice),
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
                              color: Colors.blue,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Text(
                            'Loại: $serviceType',
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Số lượng: $quantity',
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 14,
                            ),
                          ),
                          Text(
                            'Đơn giá: ${_formatCurrency(unitPrice)}',
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            }).toList(),
            const SizedBox(height: 16),
            _buildSectionTitle('Thanh toán'),
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
                        const Text('Tổng phụ:'),
                        Text(
                          _formatCurrency(subtotal),
                          style: const TextStyle(fontWeight: FontWeight.w500),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Thuế (${(taxRate * 100).toStringAsFixed(0)}%):'),
                        Text(
                          _formatCurrency(taxAmount),
                          style: const TextStyle(fontWeight: FontWeight.w500),
                        ),
                      ],
                    ),
                    const Divider(height: 24),
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
                        Text('Bảo hiểm (${(insuranceCoverage * 100).toStringAsFixed(0)}%):'),
                        Text(
                          _formatCurrency(insuranceAmount),
                          style: const TextStyle(
                            fontWeight: FontWeight.w500,
                            color: Colors.green,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Bệnh nhân trả:',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          _formatCurrency(patientPayment),
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.red,
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
