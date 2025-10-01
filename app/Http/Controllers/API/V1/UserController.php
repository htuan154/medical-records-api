<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="API endpoints for managing users"
 * )
 */
class UserController extends Controller
{
    public function __construct(private UserService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Danh sách người dùng",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="username", in="query", @OA\Schema(type="string", description="Tìm kiếm theo tên đăng nhập")),
     *     @OA\Parameter(name="staff_id", in="query", @OA\Schema(type="string", description="ID nhân viên liên kết")),
     *     @OA\Parameter(name="patient_id", in="query", @OA\Schema(type="string", description="ID bệnh nhân liên kết")),
     *     @OA\Response(response=200, description="Danh sách người dùng"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);

            // ✅ Support search by username
            $filters = [
                'username'   => $req->query('username'),  // ✅ Thêm username search
                'q'          => $req->query('q'),         // ✅ Thêm q search
                'staff_id'   => $req->query('staff_id'),
                'patient_id' => $req->query('patient_id'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Chi tiết người dùng",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin người dùng"),
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
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Tạo người dùng mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="username", type="string", example="doctor01"),
     *             @OA\Property(property="email", type="string", format="email", example="doctor01@hospital.com"),
     *             @OA\Property(property="password_hash", type="string", example="$2y$10$...", description="Password đã được hash bằng bcrypt"),
     *             @OA\Property(property="role_names", type="array", @OA\Items(type="string"), example={"doctor", "user"}),
     *             @OA\Property(property="account_type", type="string", enum={"staff","patient"}, example="staff"),
     *             @OA\Property(property="linked_staff_id", type="string", example="staff_123"),
     *             @OA\Property(property="linked_patient_id", type="string", example="patient_456"),
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
                '_id'            => 'sometimes|string',
                'type'           => 'sometimes|in:user',
                'username'       => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_]+$/',
                'email'          => 'required|email|max:255',
                // Cho phép gửi password (plain) hoặc password_hash (đã băm)
                'password'       => 'required_without:password_hash|string|min:6|max:255',
                'password_hash'  => 'required_without:password|string|min:20',
                'role_names'     => 'required|array|min:1',
                'role_names.*'   => 'string|max:50',
                'account_type'   => 'required|in:staff,doctor,patient',
                'linked_staff_id'=> 'nullable|string|max:255',
                'linked_doctor_id'=> 'nullable|string|max:255',
                'linked_patient_id'=> 'nullable|string|max:255',
                'status'         => 'nullable|in:active,inactive',
            ]);

            // Nếu client POST kèm cả _id và _rev -> xem như cập nhật
            if (!empty($data['_id']) && !empty($data['_rev'])) {
                // Hash password nếu được gửi dạng plain
                if (!empty($data['password'])) {
                    $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
                    unset($data['password']);
                }
                $res = $this->svc->update($data['_id'], $data);
                return response()->json($res['data'], $res['status']);
            }

            // Nếu có _id đã tồn tại -> trả 409 hướng dẫn dùng PUT hoặc bỏ _id
            if (!empty($data['_id'])) {
                $existing = $this->svc->find($data['_id']);
                if (($existing['status'] ?? 200) === 200) {
                    return response()->json([
                        'error' => 'conflict',
                        'message' => 'Document with the same _id already exists. Use PUT /api/v1/users/{id} with a valid _rev to update, or remove _id to auto-generate.',
                        'existing' => [
                            'id' => $data['_id'],
                            'rev' => $existing['data']['_rev'] ?? null
                        ]
                    ], 409);
                }
            }

            // Hash password nếu được gửi dạng plain
            if (!empty($data['password'])) {
                $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
                unset($data['password']);
            }

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json([
                'error' => 'validation_error', 
                'message' => 'Dữ liệu không hợp lệ', 
                'details' => $ve->errors(),
                'rules_info' => [
                    'username' => 'Bắt buộc, 3-50 ký tự, chỉ chữ cái, số và dấu gạch dưới',
                    'email' => 'Bắt buộc, định dạng email hợp lệ',
                    'password|password_hash' => 'Bắt buộc: cung cấp password (sẽ được hash) hoặc password_hash đã hash',
                    'role_names' => 'Bắt buộc, ít nhất 1 role, ví dụ: ["admin", "user"]',
                    'account_type' => 'Bắt buộc: staff, doctor, hoặc patient',
                    'status' => 'Tùy chọn: active hoặc inactive'
                ]
            ], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Cập nhật người dùng",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="username", type="string", example="doctor02"),
     *             @OA\Property(property="email", type="string", format="email", example="doctor02@hospital.com"),
     *             @OA\Property(property="role_names", type="array", @OA\Items(type="string")),
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
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Xóa người dùng",
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
        try {
            $rev = $request->query('rev');

            // Nếu không có rev, lấy document hiện tại
            if (empty($rev)) {
                $result = $this->svc->find($id);

                // Check nếu service trả về status 404
                if ($result['status'] === 404) {
                    return response()->json(['error' => 'not_found', 'message' => 'User not found'], 404);
                }

                // Lấy _rev từ data
                $rev = $result['data']['_rev'] ?? null;
                if (!$rev) {
                    return response()->json([
                        'error' => 'missing_rev',
                        'message' => 'Cannot get document revision'
                    ], 400);
                }
            }

            // Thực hiện xóa và check kết quả
            $deleteResult = $this->svc->delete($id, $rev);

            // Check status từ service
            if ($deleteResult['status'] !== 200) {
                return response()->json($deleteResult['data'], $deleteResult['status']);
            }

            // Verify delete thành công
            if (!isset($deleteResult['data']['ok']) || !$deleteResult['data']['ok']) {
                return response()->json([
                    'error' => 'delete_failed',
                    'message' => 'Delete operation did not complete successfully'
                ], 500);
            }

            return response()->json([
                'ok' => true,
                'message' => 'User deleted successfully'
            ], 200);

        } catch (Throwable $e) {
            // Nếu conflict, thử lại với rev mới nhất
            if (str_contains(strtolower($e->getMessage()), 'conflict')) {
                try {
                    $result = $this->svc->find($id);
                    if ($result['status'] === 404) {
                        return response()->json(['error' => 'not_found'], 404);
                    }

                    $latestRev = $result['data']['_rev'] ?? null;
                    if (!$latestRev) {
                        return response()->json(['error' => 'missing_rev'], 400);
                    }

                    $retryResult = $this->svc->delete($id, $latestRev);

                    if ($retryResult['status'] !== 200) {
                        return response()->json($retryResult['data'], $retryResult['status']);
                    }

                    return response()->json([
                        'ok' => true,
                        'message' => 'User deleted successfully (retry)'
                    ], 200);

                } catch (Throwable $retryError) {
                    return response()->json([
                        'error' => 'delete_failed',
                        'message' => 'Failed to delete after retry: ' . $retryError->getMessage()
                    ], 500);
                }
            }

            return response()->json([
                'error' => 'server_error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
