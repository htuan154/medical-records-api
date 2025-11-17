<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Invoices",
 *     description="API endpoints for managing invoices"
 * )
 */
class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices",
     *     tags={"Invoices"},
     *     summary="Danh sách hóa đơn",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="patient_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="number", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="start", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="payment_status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Danh sách hóa đơn"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function index(Request $req)
    {
        try {
            // $this->svc->ensureDesignDoc(); // ✅ Moved to /setup/all

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'patient_id'        => $req->query('patient_id'),
                'medical_record_id' => $req->query('medical_record_id'),
                'number'            => $req->query('number') ?: $req->query('q'),
                'start'             => $req->query('start'),
                'end'               => $req->query('end'),
                'payment_status'    => $req->query('payment_status'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Chi tiết hóa đơn",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin hóa đơn"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices",
     *     tags={"Invoices"},
     *     summary="Tạo hóa đơn mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="patient_id", type="string", example="patient_123"),
     *             @OA\Property(property="medical_record_id", type="string", example="record_456"),
     *             @OA\Property(property="invoice_info.invoice_number", type="string", example="INV-2024-001"),
     *             @OA\Property(property="invoice_info.invoice_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="payment_info.total_amount", type="number", example=500000),
     *             @OA\Property(property="payment_status", type="string", example="pending"),
     *             @OA\Property(property="services", type="array", @OA\Items(
     *                 @OA\Property(property="service_type", type="string", example="consultation"),
     *                 @OA\Property(property="description", type="string", example="Khám tổng quát"),
     *                 @OA\Property(property="quantity", type="number", example=1),
     *                 @OA\Property(property="unit_price", type="number", example=200000),
     *                 @OA\Property(property="total_price", type="number", example=200000)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công"),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=422, description="Lỗi validation"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                                   => 'sometimes|string',
                'type'                                  => 'sometimes|in:invoice',
                'patient_id'                            => 'required|string',
                'medical_record_id'                     => 'nullable|string',
                'invoice_info.invoice_number'           => 'required|string',
                'invoice_info.invoice_date'             => 'required|date',
                'invoice_info.due_date'                 => 'nullable|date',
                'services'                              => 'nullable|array',
                'services.*.service_type'               => 'nullable|string',
                'services.*.description'                => 'nullable|string',
                'services.*.quantity'                   => 'nullable|numeric',
                'services.*.unit_price'                 => 'nullable|numeric',
                'services.*.total_price'                => 'nullable|numeric',
                'payment_info.subtotal'                 => 'nullable|numeric',
                'payment_info.tax_rate'                 => 'nullable|numeric',
                'payment_info.tax_amount'               => 'nullable|numeric',
                'payment_info.total_amount'             => 'nullable|numeric',
                'payment_info.insurance_coverage'       => 'nullable|numeric',
                'payment_info.insurance_amount'         => 'nullable|numeric',
                'payment_info.patient_payment'          => 'nullable|numeric',
                'payment_status'                         => 'nullable|string',
                'payment_method'                         => 'nullable|string',
                'paid_date'                              => 'nullable|date',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Cập nhật hóa đơn",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="payment_status", type="string", example="paid"),
     *             @OA\Property(property="paid_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công"),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/invoices/{id}",
     *     tags={"Invoices"},
     *     summary="Xóa hóa đơn",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="rev", in="query", required=false, @OA\Schema(type="string"), description="Optional; server will auto-resolve latest rev if omitted"),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{id}/download",
     *     tags={"Invoices"},
     *     summary="Download PDF hóa đơn",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="PDF file", @OA\MediaType(mediaType="application/pdf")),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function download(string $id)
    {
        try {
            $res = $this->svc->find($id);
            
            if ($res['status'] !== 200) {
                return response()->json($res['data'], $res['status']);
            }
            
            $invoice = $res['data'];
            
            // ✅ Generate printable HTML (browser can print to PDF)
            $html = $this->generateInvoiceHtml($invoice);
            
            // ✅ Return HTML for browser print
            // User can use Ctrl+P or Print button to save as PDF
            return response($html, 200)
                ->header('Content-Type', 'text/html; charset=utf-8')
                ->header('Content-Disposition', 'inline; filename="invoice_' . ($invoice['invoice_info']['invoice_number'] ?? $id) . '.html"');
                                       
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }
    
    /**
     * Generate HTML for invoice (printable version with print button)
     */
    private function generateInvoiceHtml(array $invoice): string
    {
        $invoiceInfo = $invoice['invoice_info'] ?? [];
        $paymentInfo = $invoice['payment_info'] ?? [];
        
        $number = $invoiceInfo['invoice_number'] ?? 'N/A';
        $date = date('d/m/Y', strtotime($invoiceInfo['invoice_date'] ?? 'now'));
        $subtotal = $paymentInfo['subtotal'] ?? 0;
        $insuranceAmount = $paymentInfo['insurance_amount'] ?? 0;
        $total = $paymentInfo['total_amount'] ?? 0;
        $patientPayment = $paymentInfo['patient_payment'] ?? 0;
        $status = $invoice['payment_status'] ?? 'unpaid';
        
        $statusText = match($status) {
            'paid' => 'Đã thanh toán',
            'partial' => 'Thanh toán một phần',
            'void' => 'Đã hủy',
            default => 'Chưa thanh toán'
        };
        
        $html = "<!DOCTYPE html>\n<html lang='vi'>\n<head>\n";
        $html .= "<meta charset='utf-8'>\n";
        $html .= "<title>Hóa đơn {$number}</title>\n";
        $html .= "<style>\n";
        $html .= "body { font-family: Arial, sans-serif; padding: 30px; }\n";
        $html .= ".print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }\n";
        $html .= ".print-btn:hover { background: #2980b9; }\n";
        $html .= ".header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }\n";
        $html .= ".invoice-meta { display: flex; justify-content: space-between; margin: 20px 0; }\n";
        $html .= "table { width: 100%; border-collapse: collapse; margin: 20px 0; }\n";
        $html .= "th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }\n";
        $html .= "th { background: #34495e; color: white; }\n";
        $html .= ".text-right { text-align: right; }\n";
        $html .= ".totals { margin-top: 20px; text-align: right; }\n";
        $html .= ".totals-row { display: flex; justify-content: flex-end; padding: 8px 0; }\n";
        $html .= ".totals-label { width: 200px; text-align: right; padding-right: 20px; }\n";
        $html .= ".totals-value { width: 200px; text-align: right; font-weight: 600; }\n";
        $html .= ".grand-total { border-top: 2px solid #333; margin-top: 10px; padding-top: 15px; font-size: 20px; }\n";
        $html .= "@media print { .print-btn { display: none; } }\n";
        $html .= "</style>\n";
        $html .= "</head>\n<body>\n";
        
        $html .= "<button class='print-btn' onclick='window.print()'>🖨️ In / Lưu PDF</button>\n";
        $html .= "<div class='header'>\n";
        $html .= "<h1>HÓA ĐƠN THANH TOÁN</h1>\n";
        $html .= "<p>Số: <strong>{$number}</strong> | Ngày: <strong>{$date}</strong></p>\n";
        $html .= "<p>Trạng thái: <strong>{$statusText}</strong></p>\n";
        $html .= "</div>\n";
        
        $html .= "<table>\n<thead>\n<tr>\n";
        $html .= "<th>Dịch vụ</th>\n<th style='width:100px;text-align:center'>Số lượng</th>\n";
        $html .= "<th style='width:150px;text-align:right'>Đơn giá</th>\n";
        $html .= "<th style='width:150px;text-align:right'>Thành tiền</th>\n";
        $html .= "</tr>\n</thead>\n<tbody>\n";
        
        if (!empty($invoice['services']) && is_array($invoice['services'])) {
            foreach ($invoice['services'] as $service) {
                $desc = htmlspecialchars($service['description'] ?? 'N/A');
                $qty = $service['quantity'] ?? 1;
                $price = number_format($service['unit_price'] ?? 0, 0, ',', '.');
                $itemTotal = number_format($service['total_price'] ?? 0, 0, ',', '.');
                
                $html .= "<tr>\n";
                $html .= "<td>{$desc}</td>\n";
                $html .= "<td style='text-align:center'>{$qty}</td>\n";
                $html .= "<td style='text-align:right'>{$price} đ</td>\n";
                $html .= "<td style='text-align:right'>{$itemTotal} đ</td>\n";
                $html .= "</tr>\n";
            }
        } else {
            $html .= "<tr><td colspan='4' style='text-align:center'>Không có dịch vụ</td></tr>\n";
        }
        
        $html .= "</tbody>\n</table>\n";
        
        $html .= "<div class='totals'>\n";
        $html .= "<div class='totals-row'>\n";
        $html .= "<div class='totals-label'>Tạm tính:</div>\n";
        $html .= "<div class='totals-value'>" . number_format($subtotal, 0, ',', '.') . " đ</div>\n";
        $html .= "</div>\n";
        
        if ($insuranceAmount > 0) {
            $html .= "<div class='totals-row'>\n";
            $html .= "<div class='totals-label'>Bảo hiểm chi trả:</div>\n";
            $html .= "<div class='totals-value'>- " . number_format($insuranceAmount, 0, ',', '.') . " đ</div>\n";
            $html .= "</div>\n";
        }
        
        $html .= "<div class='totals-row grand-total'>\n";
        $html .= "<div class='totals-label'>TỔNG CỘNG:</div>\n";
        $html .= "<div class='totals-value'>" . number_format($total, 0, ',', '.') . " đ</div>\n";
        $html .= "</div>\n";
        
        $html .= "<div class='totals-row' style='color:#27ae60;margin-top:10px'>\n";
        $html .= "<div class='totals-label'>Bệnh nhân thanh toán:</div>\n";
        $html .= "<div class='totals-value'>" . number_format($patientPayment, 0, ',', '.') . " đ</div>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";
        
        $html .= "<div style='margin-top:50px;text-align:center;color:#777;font-size:12px'>\n";
        $html .= "<p>Cảm ơn quý khách! Nhấn nút 'In / Lưu PDF' hoặc Ctrl+P để in/lưu</p>\n";
        $html .= "</div>\n";
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
}
