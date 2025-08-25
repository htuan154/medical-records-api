<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\MedicalRecordService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Medical Records",
 *     description="API endpoints for managing medical records"
 * )
 */
class MedicalRecordController extends Controller
{
    public function __construct(private MedicalRecordService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/medical-records",
     *     tags={"Medical Records"},
     *     summary="Danh sách hồ sơ bệnh án",
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="patient_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="doctor_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="appointment_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="start", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="primary_icd", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"scheduled","completed","cancelled"})),
     *     @OA\Response(response=200, description="Danh sách hồ sơ bệnh án"),
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
                'doctor_id'      => $req->query('doctor_id'),
                'appointment_id' => $req->query('appointment_id'),
                'start'          => $req->query('start'),
                'end'            => $req->query('end'),
                'primary_icd'    => $req->query('primary_icd'),
                'status'         => $req->query('status'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/medical-records/{id}",
     *     tags={"Medical Records"},
     *     summary="Chi tiết hồ sơ bệnh án",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin hồ sơ bệnh án"),
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
     *     path="/api/v1/medical-records",
     *     tags={"Medical Records"},
     *     summary="Tạo hồ sơ bệnh án mới",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="patient_id", type="string", example="patient_123"),
     *             @OA\Property(property="doctor_id", type="string", example="doctor_456"),
     *             @OA\Property(property="visit_info.visit_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="visit_info.visit_type", type="string", example="consultation"),
     *             @OA\Property(property="visit_info.chief_complaint", type="string", example="Đau đầu"),
     *             @OA\Property(property="visit_info.appointment_id", type="string", example="appointment_789"),
     *             @OA\Property(property="examination", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="diagnosis", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="treatment_plan", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="status", type="string", enum={"scheduled","completed","cancelled"}, example="completed")
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
                'type'                                  => 'sometimes|in:medical_record',
                'patient_id'                            => 'required|string',
                'doctor_id'                             => 'required|string',
                'visit_info.visit_date'                 => 'required|date',
                'visit_info.visit_type'                 => 'nullable|string',
                'visit_info.chief_complaint'            => 'nullable|string',
                'visit_info.appointment_id'             => 'nullable|string',
                'examination'                            => 'nullable|array',
                'diagnosis'                              => 'nullable|array',
                'treatment_plan'                         => 'nullable|array',
                'attachments'                            => 'nullable|array',
                'status'                                 => 'nullable|in:scheduled,completed,cancelled',
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
     *     path="/api/v1/medical-records/{id}",
     *     tags={"Medical Records"},
     *     summary="Cập nhật hồ sơ bệnh án",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="examination", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="diagnosis", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="status", type="string", enum={"scheduled","completed","cancelled"})
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
     *     path="/api/v1/medical-records/{id}",
     *     tags={"Medical Records"},
     *     summary="Xóa hồ sơ bệnh án",
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
