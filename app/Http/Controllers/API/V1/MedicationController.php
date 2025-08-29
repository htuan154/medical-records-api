<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\MedicationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Medications",
 *     description="API endpoints for managing medications"
 * )
 */
class MedicationController extends Controller
{
    public function __construct(private MedicationService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/medications",
     *     tags={"Medications"},
     *     summary="Danh sách thuốc",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="q", in="query", @OA\Schema(type="string", description="Tìm kiếm theo tên thuốc")),
     *     @OA\Parameter(name="barcode", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="class", in="query", @OA\Schema(type="string", description="Phân loại thuốc")),
     *     @OA\Parameter(name="start", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end", in="query", @OA\Schema(type="string", format="date", description="Ngày hết hạn")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive"})),
     *     @OA\Parameter(name="low_stock", in="query", @OA\Schema(type="integer", description="Ngưỡng tồn kho thấp")),
     *     @OA\Response(response=200, description="Danh sách thuốc"),
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
                'q'        => $req->query('q'),
                'barcode'  => $req->query('barcode'),
                'class'    => $req->query('class'),
                'start'    => $req->query('start'),
                'end'      => $req->query('end'),
                'status'   => $req->query('status'),
                'low_stock'=> $req->query('low_stock'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/medications/{id}",
     *     tags={"Medications"},
     *     summary="Chi tiết thuốc",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin thuốc"),
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
     *     path="/api/v1/medications",
     *     tags={"Medications"},
     *     summary="Tạo thuốc mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="medication_info.name", type="string", example="Paracetamol"),
     *             @OA\Property(property="medication_info.generic_name", type="string", example="Acetaminophen"),
     *             @OA\Property(property="medication_info.strength", type="string", example="500mg"),
     *             @OA\Property(property="medication_info.dosage_form", type="string", example="tablet"),
     *             @OA\Property(property="medication_info.manufacturer", type="string", example="ABC Pharma"),
     *             @OA\Property(property="medication_info.barcode", type="string", example="1234567890123"),
     *             @OA\Property(property="clinical_info.therapeutic_class", type="string", example="Analgesic"),
     *             @OA\Property(property="clinical_info.indications", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="clinical_info.contraindications", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="clinical_info.side_effects", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="clinical_info.drug_interactions", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="inventory.current_stock", type="integer", example=100),
     *             @OA\Property(property="inventory.unit_cost", type="number", example=5.50),
     *             @OA\Property(property="inventory.expiry_date", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(property="inventory.supplier", type="string", example="Supplier ABC"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active")
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
                'type'                                  => 'sometimes|in:medication',
                'medication_info.name'                  => 'required|string',
                'medication_info.generic_name'          => 'nullable|string',
                'medication_info.strength'              => 'nullable|string',
                'medication_info.dosage_form'           => 'nullable|string',
                'medication_info.manufacturer'          => 'nullable|string',
                'medication_info.barcode'               => 'nullable|string',
                'clinical_info.therapeutic_class'       => 'nullable|string',
                'clinical_info.indications'             => 'nullable|array',
                'clinical_info.contraindications'       => 'nullable|array',
                'clinical_info.side_effects'            => 'nullable|array',
                'clinical_info.drug_interactions'       => 'nullable|array',
                'inventory.current_stock'               => 'nullable|integer',
                'inventory.unit_cost'                   => 'nullable|numeric',
                'inventory.expiry_date'                 => 'nullable|date',
                'inventory.supplier'                    => 'nullable|string',
                'status'                                 => 'nullable|in:active,inactive',
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
     *     path="/api/v1/medications/{id}",
     *     tags={"Medications"},
     *     summary="Cập nhật thuốc",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="medication_info.name", type="string", example="Paracetamol"),
     *             @OA\Property(property="inventory.current_stock", type="integer", example=150),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
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
     *     path="/api/v1/medications/{id}",
     *     tags={"Medications"},
     *     summary="Xóa thuốc",
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

    /**
     * @OA\Post(
     *     path="/api/v1/medications/{id}/stock-increase",
     *     tags={"Medications"},
     *     summary="Tăng tồn kho thuốc",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="rev", type="string", example="1-abc123"),
     *             @OA\Property(property="delta", type="integer", example=50, description="Số lượng tăng")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Tăng tồn kho thành công"),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function stockIncrease(Request $req, string $id)
    {
        $rev   = $req->input('rev');
        $delta = (int) $req->input('delta', 0);
        $res = $this->svc->adjustStock($id, abs($delta), $rev);
        return response()->json($res['data'], $res['status']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/medications/{id}/stock-decrease",
     *     tags={"Medications"},
     *     summary="Giảm tồn kho thuốc",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="rev", type="string", example="1-abc123"),
     *             @OA\Property(property="delta", type="integer", example=20, description="Số lượng giảm")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Giảm tồn kho thành công"),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function stockDecrease(Request $req, string $id)
    {
        $rev   = $req->input('rev');
        $delta = (int) $req->input('delta', 0);
        $res = $this->svc->adjustStock($id, -abs($delta), $rev);
        return response()->json($res['data'], $res['status']);
    }
}
