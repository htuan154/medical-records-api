<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\TreatmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Treatments",
 *     description="API endpoints for managing treatments"
 * )
 */
class TreatmentController extends Controller
{
    public function __construct(private TreatmentService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/treatments",
     *     tags={"Treatments"},
     *     summary="Danh sách điều trị",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="patient_id", in="query", @OA\Schema(type="string", description="ID bệnh nhân")),
     *     @OA\Parameter(name="doctor_id", in="query", @OA\Schema(type="string", description="ID bác sĩ")),
     *     @OA\Parameter(name="medical_record_id", in="query", @OA\Schema(type="string", description="ID hồ sơ bệnh án")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","completed","paused","cancelled"})),
     *     @OA\Parameter(name="treatment_type", in="query", @OA\Schema(type="string", description="Loại điều trị")),
     *     @OA\Parameter(name="medication_id", in="query", @OA\Schema(type="string", description="ID thuốc")),
     *     @OA\Parameter(name="start", in="query", @OA\Schema(type="string", format="date", description="Ngày bắt đầu")),
     *     @OA\Parameter(name="end", in="query", @OA\Schema(type="string", format="date", description="Ngày kết thúc")),
     *     @OA\Response(response=200, description="Danh sách điều trị"),
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
                'patient_id'       => $req->query('patient_id'),
                'doctor_id'        => $req->query('doctor_id'),
                'medical_record_id'=> $req->query('medical_record_id'),
                'status'           => $req->query('status'),
                'treatment_type'   => $req->query('treatment_type'),
                'medication_id'    => $req->query('medication_id'),
                'start'            => $req->query('start'),
                'end'              => $req->query('end'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/treatments/{id}",
     *     tags={"Treatments"},
     *     summary="Chi tiết điều trị",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin điều trị"),
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
     *     path="/api/v1/treatments",
     *     tags={"Treatments"},
     *     summary="Tạo điều trị mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="patient_id", type="string", example="patient_123"),
     *             @OA\Property(property="doctor_id", type="string", example="doctor_456"),
     *             @OA\Property(property="medical_record_id", type="string", example="record_789"),
     *             @OA\Property(
     *                 property="treatment_info",
     *                 type="object",
     *                 @OA\Property(property="treatment_name", type="string", example="Điều trị cao huyết áp"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-01-30"),
     *                 @OA\Property(property="duration_days", type="integer", example=30),
     *                 @OA\Property(property="treatment_type", type="string", example="medication")
     *             ),
     *             @OA\Property(
     *                 property="medications",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="medication_id", type="string", example="med_123"),
     *                     @OA\Property(property="name", type="string", example="Amlodipine"),
     *                     @OA\Property(property="dosage", type="string", example="5mg"),
     *                     @OA\Property(property="frequency", type="string", example="1 lần/ngày"),
     *                     @OA\Property(property="route", type="string", example="uống"),
     *                     @OA\Property(property="instructions", type="string", example="Uống sau ăn"),
     *                     @OA\Property(property="quantity_prescribed", type="number", example=30)
     *                 )
     *             ),
     *             @OA\Property(property="monitoring", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="status", type="string", enum={"active","completed","paused","cancelled"}, example="active")
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
                '_id'                           => 'sometimes|string',
                '_rev'                          => 'sometimes|string',
                'type'                          => 'sometimes|in:treatment',
                'patient_id'                    => 'required|string',
                'doctor_id'                     => 'required|string',
                'medical_record_id'             => 'nullable|string',
                'treatment_info.treatment_name' => 'required|string',
                'treatment_info.start_date'     => 'required|date',
                'treatment_info.end_date'       => 'nullable|date',
                'treatment_info.duration_days'  => 'nullable|integer',
                'treatment_info.treatment_type' => 'nullable|string',
                'medications'                   => 'nullable|array',
                'medications.*.medication_id'   => 'nullable|string',
                'medications.*.name'            => 'nullable|string',
                'medications.*.dosage'          => 'nullable|string',
                'medications.*.frequency'       => 'nullable|string',
                'medications.*.route'           => 'nullable|string',
                'medications.*.instructions'    => 'nullable|string',
                'medications.*.quantity_prescribed' => 'nullable|numeric',
                'monitoring'                    => 'nullable|array',
                'status'                         => 'nullable|in:active,completed,paused,cancelled',
            ]);

            // Nếu client gửi kèm _id + _rev -> coi như POST-as-update (tiện test qua Swagger)
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
     *     path="/api/v1/treatments/{id}",
     *     tags={"Treatments"},
     *     summary="Cập nhật điều trị",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="treatment_info.treatment_name", type="string", example="Điều trị tiểu đường"),
     *             @OA\Property(property="status", type="string", enum={"active","completed","paused","cancelled"})
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
     *     path="/api/v1/treatments/{id}",
     *     tags={"Treatments"},
     *     summary="Xóa điều trị",
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
