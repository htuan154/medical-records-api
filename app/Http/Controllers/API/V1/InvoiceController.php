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
            $this->svc->ensureDesignDoc();

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
     *     @OA\Parameter(name="rev", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=400, description="Thiếu rev parameter"),
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
}
