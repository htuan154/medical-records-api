<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\DoctorService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Doctors",
 *     description="API endpoints for managing doctors"
 * )
 */
class DoctorController extends Controller
{
    public function __construct(private DoctorService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/doctors",
     *     tags={"Doctors"},
     *     summary="Danh sách bác sĩ",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="q", in="query", @OA\Schema(type="string", example="Nguyễn")),
     *     @OA\Parameter(name="specialty", in="query", @OA\Schema(type="string", example="cardiology")),
     *     @OA\Response(response=200, description="Danh sách bác sĩ"),
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
                'q'         => trim((string) $req->query('q', '')),
                'specialty' => $req->query('specialty'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/doctors/{id}",
     *     tags={"Doctors"},
     *     summary="Chi tiết bác sĩ",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin bác sĩ"),
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
     *     path="/api/v1/doctors",
     *     tags={"Doctors"},
     *     summary="Tạo bác sĩ mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="personal_info.full_name", type="string", example="Dr. Nguyễn Văn A"),
     *             @OA\Property(property="personal_info.birth_date", type="string", format="date", example="1980-01-15"),
     *             @OA\Property(property="personal_info.gender", type="string", enum={"male","female","other"}, example="male"),
     *             @OA\Property(property="personal_info.phone", type="string", example="0901234567"),
     *             @OA\Property(property="personal_info.email", type="string", example="doctor@example.com"),
     *             @OA\Property(property="professional_info.specialty", type="string", example="cardiology"),
     *             @OA\Property(property="professional_info.license_number", type="string", example="DOC123456"),
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
                'type'                                  => 'sometimes|in:doctor',
                'personal_info.full_name'               => 'required|string',
                'personal_info.birth_date'              => 'nullable|date',
                'personal_info.gender'                  => 'nullable|in:male,female,other',
                'personal_info.phone'                   => 'nullable|string',
                'personal_info.email'                   => 'nullable|email',
                'professional_info.license_number'      => 'nullable|string',
                'professional_info.specialty'           => 'nullable|string',
                'professional_info.sub_specialties'     => 'nullable|array',
                'professional_info.experience_years'    => 'nullable|integer',
                'professional_info.education'           => 'nullable|array',
                'professional_info.certifications'      => 'nullable|array',
                'schedule.working_days'                 => 'nullable|array',
                'schedule.working_days.*'               => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday,mon,tue,wed,thu,fri,sat,sun',
                'schedule.working_hours.start'          => 'nullable|string|date_format:H:i',
                'schedule.working_hours.end'            => 'nullable|string|date_format:H:i',
                'schedule.break_time.start'             => 'nullable|string|date_format:H:i',
                'schedule.break_time.end'               => 'nullable|string|date_format:H:i',
                'status'                                => 'nullable|in:active,inactive',
            ]);

            // Nếu client POST kèm cả _id và _rev -> xem như yêu cầu update
            if (!empty($data['_id']) && !empty($data['_rev'])) {
                $res = $this->svc->update($data['_id'], $data);
                return response()->json($res['data'], $res['status']);
            }

            // Tránh xung đột: nếu client gửi _id đã tồn tại thì trả về 409 với hướng dẫn
            if (!empty($data['_id'])) {
                $existing = $this->svc->find($data['_id']);
                if (($existing['status'] ?? 200) === 200) {
                    return response()->json([
                        'error' => 'conflict',
                        'message' => 'Document with the same _id already exists. Use PUT /api/v1/doctors/{id} with a valid _rev to update, or remove _id to auto-generate.',
                        'existing' => [
                            'id' => $data['_id'],
                            'rev' => $existing['data']['_rev'] ?? null
                        ]
                    ], 409);
                }
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
     *     path="/api/v1/doctors/{id}",
     *     tags={"Doctors"},
     *     summary="Cập nhật bác sĩ",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="personal_info.full_name", type="string"),
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
     *     path="/api/v1/doctors/{id}",
     *     tags={"Doctors"},
     *     summary="Xóa bác sĩ",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="rev", in="query", required=true, @OA\Schema(type="string", example="1-abc123")),
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
