<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\MedicalTestService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Medical Tests",
 *     description="API endpoints for managing medical tests"
 * )
 */
class MedicalTestController extends Controller
{
    public function __construct(private MedicalTestService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/medical-tests",
     *     tags={"Medical Tests"},
     *     summary="Danh sách xét nghiệm y tế",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="patient_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="doctor_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="medical_record_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="test_type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="start", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Danh sách xét nghiệm y tế"),
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
                'patient_id'       => $req->query('patient_id'),
                'doctor_id'        => $req->query('doctor_id'),
                'medical_record_id'=> $req->query('medical_record_id'),
                'test_type'        => $req->query('test_type'),
                'start'            => $req->query('start'),
                'end'              => $req->query('end'),
                'status'           => $req->query('status'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/medical-tests/{id}",
     *     tags={"Medical Tests"},
     *     summary="Chi tiết xét nghiệm y tế",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin xét nghiệm y tế"),
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
     *     path="/api/v1/medical-tests",
     *     tags={"Medical Tests"},
     *     summary="Tạo xét nghiệm y tế mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="patient_id", type="string", example="patient_123"),
     *             @OA\Property(property="doctor_id", type="string", example="doctor_456"),
     *             @OA\Property(property="medical_record_id", type="string", example="record_789"),
     *             @OA\Property(property="test_info.test_type", type="string", example="blood_test"),
     *             @OA\Property(property="test_info.test_name", type="string", example="Xét nghiệm máu tổng quát"),
     *             @OA\Property(property="test_info.ordered_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="test_info.sample_collected_date", type="string", format="date", example="2024-01-16"),
     *             @OA\Property(property="test_info.result_date", type="string", format="date", example="2024-01-17"),
     *             @OA\Property(property="results", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="interpretation", type="string", example="Kết quả bình thường"),
     *             @OA\Property(property="status", type="string", example="completed"),
     *             @OA\Property(property="lab_technician", type="string", example="tech_123")
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
                '_id'                         => 'sometimes|string',
                '_rev'                        => 'sometimes|string',
                'type'                        => 'sometimes|in:medical_test',
                'patient_id'                  => 'required|string',
                'doctor_id'                   => 'required|string',
                'medical_record_id'           => 'nullable|string',
                'test_info.test_type'         => 'required|string',
                'test_info.test_name'         => 'nullable|string',
                'test_info.ordered_date'      => 'required|date',
                'test_info.sample_collected_date' => 'nullable|date',
                'test_info.result_date'       => 'nullable|date',
                'results'                     => 'nullable|array',
                'interpretation'              => 'nullable|string',
                'status'                      => 'nullable|string',
                'lab_technician'              => 'nullable|string',
            ]);

            // Hỗ trợ POST-as-update nếu có _id + _rev
            if (!empty($data['_id']) && !empty($data['_rev'])) {
                $res = $this->svc->update($data['_id'], $data);
                return response()->json($res['data'], $res['status']);
            }

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
     *     path="/api/v1/medical-tests/{id}",
     *     tags={"Medical Tests"},
     *     summary="Cập nhật xét nghiệm y tế",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="results", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="interpretation", type="string", example="Kết quả bình thường"),
     *             @OA\Property(property="status", type="string", example="completed")
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
     *     path="/api/v1/medical-tests/{id}",
     *     tags={"Medical Tests"},
     *     summary="Xóa xét nghiệm y tế",
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
    public function destroy(Request $request, string $id)
    {
        $rev = $request->query('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
