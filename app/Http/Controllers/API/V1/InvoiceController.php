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
                'patient_id'     => $req->query('patient_id'),
                'number'         => $req->query('number'),
                'start'          => $req->query('start'),
                'end'            => $req->query('end'),
                'payment_status' => $req->query('payment_status'),
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
            
            // Generate simple PDF content (you can use a library like TCPDF or DomPDF later)
            $html = $this->generateInvoiceHtml($invoice);
            
            // For now, return HTML as PDF placeholder
            // TODO: Use proper PDF library (TCPDF, DomPDF, etc.)
            return response($html, 200)->header('Content-Type', 'application/pdf')
                                       ->header('Content-Disposition', 'attachment; filename="invoice_' . ($invoice['invoice_info']['invoice_number'] ?? $id) . '.pdf"');
                                       
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }
    
    /**
     * Generate HTML for invoice (simple version)
     */
    private function generateInvoiceHtml(array $invoice): string
    {
        $number = $invoice['invoice_info']['invoice_number'] ?? 'N/A';
        $date = $invoice['invoice_info']['invoice_date'] ?? 'N/A';
        $total = $invoice['payment_info']['total_amount'] ?? 0;
        $status = $invoice['payment_status'] ?? 'pending';
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hóa đơn {$number}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .info { margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; font-size: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>HÓA ĐƠN</h1>
        <p>Số: {$number}</p>
        <p>Ngày: {$date}</p>
    </div>
    
    <div class="info">
        <p><strong>Bệnh nhân:</strong> {$invoice['patient_id']}</p>
        <p><strong>Trạng thái:</strong> {$status}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Dịch vụ</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
HTML;

        if (!empty($invoice['services']) && is_array($invoice['services'])) {
            foreach ($invoice['services'] as $service) {
                $desc = $service['description'] ?? '';
                $qty = $service['quantity'] ?? 1;
                $price = number_format($service['unit_price'] ?? 0);
                $total = number_format($service['total_price'] ?? 0);
                
                $html .= "<tr><td>{$desc}</td><td>{$qty}</td><td>{$price} VNĐ</td><td>{$total} VNĐ</td></tr>\n";
            }
        }

        $totalFormatted = number_format($total);
        
        $html .= <<<HTML
        </tbody>
    </table>
    
    <p class="total" style="text-align: right; margin-top: 20px;">
        Tổng cộng: {$totalFormatted} VNĐ
    </p>
</body>
</html>
HTML;

        return $html;
    }
}
