<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\PatientService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Patients",
 *     description="API endpoints for managing patients"
 * )
 */
class PatientController extends Controller
{
    public function __construct(private PatientService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/patients",
     *     tags={"Patients"},
     *     summary="Danh sách bệnh nhân",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="q", in="query", @OA\Schema(type="string", description="Tìm kiếm theo tên bệnh nhân")),
     *     @OA\Response(response=200, description="Danh sách bệnh nhân"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $q     = trim((string) $req->query('q', ''));

            return response()->json($this->svc->list($limit, $skip, $q), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/patients/{id}",
     *     tags={"Patients"},
     *     summary="Chi tiết bệnh nhân",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin bệnh nhân"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function show(string $id)
    {
        try {
            $res = $this->svc->find($id);
            return response()->json($res['data'], $res['status']);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/patients",
     *     tags={"Patients"},
     *     summary="Tạo bệnh nhân mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="personal_info.full_name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="personal_info.gender", type="string", example="Nam"),
     *             @OA\Property(property="personal_info.phone", type="string", example="0123456789"),
     *             @OA\Property(property="personal_info.birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="personal_info.id_number", type="string", example="123456789"),
     *             @OA\Property(property="medical_info", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="address", type="array", @OA\Items(type="object")),
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
                '_id'                       => 'sometimes|string',
                'type'                      => 'sometimes|in:patient',
                'personal_info.full_name'   => 'required|string',
                'personal_info.gender'      => 'nullable|string',
                'personal_info.phone'       => 'nullable|string',
                'personal_info.birth_date'  => 'nullable|date',
                'personal_info.id_number'   => 'nullable|string',
                'medical_info'              => 'nullable|array',
                'address'                   => 'nullable|array',
                'status'                    => 'nullable|in:active,inactive',
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
     *     path="/api/v1/patients/{id}",
     *     tags={"Patients"},
     *     summary="Cập nhật bệnh nhân",
     *    security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="personal_info.full_name", type="string", example="Nguyễn Văn B"),
     *             @OA\Property(property="personal_info.phone", type="string", example="0987654321"),
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
        try {
            $res = $this->svc->update($id, $req->all());
            return response()->json($res['data'], $res['status']);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/patients/{id}",
     *     tags={"Patients"},
     *     summary="Xóa bệnh nhân",
     *      security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="rev", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=400, description="Thiếu rev parameter"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
public function destroy(\Illuminate\Http\Request $request, string $id)
{
    try {
        // 1) Lấy rev từ client
        $rev = $request->query('rev');

        // 2) Nếu không có rev -> fetch doc để lấy _rev mới nhất
        if (empty($rev)) {
            $result = $this->svc->find($id);

            // ✅ Check status đúng cách
            if ($result['status'] === 404) {
                return response()->json(['error' => 'not_found'], 404);
            }

            // ✅ Lấy _rev từ data
            $rev = $result['data']['_rev'] ?? null;
            if (!$rev) {
                return response()->json([
                    'error' => 'missing_rev',
                    'reason' => 'Cannot get document revision'
                ], 400);
            }
        }

        // 3) Xóa lần đầu
        $res = $this->svc->delete($id, $rev);

        // 4) Nếu conflict -> retry với rev mới nhất
        if (($res['status'] ?? 0) === 409 || ($res['data']['error'] ?? '') === 'conflict') {
            $result = $this->svc->find($id);
            if ($result['status'] === 404) {
                return response()->json(['error' => 'not_found'], 404);
            }

            $latestRev = $result['data']['_rev'] ?? null;
            if ($latestRev) {
                $res = $this->svc->delete($id, $latestRev);
            }
        }

        return response()->json($res['data'], $res['status'] ?? 200);

    } catch (\Throwable $e) {
        return response()->json([
            'ok'     => false,
            'error'  => 'server_error',
            'reason' => $e->getMessage(),
        ], 500);
    }
}



}
