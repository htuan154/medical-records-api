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
            $filters = [
                'username'   => $req->query('username'),
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
                'username'       => 'required|string',
                'email'          => 'nullable|email',
                'password_hash'  => 'required|string', // gửi hash sẵn (bcrypt)
                'role_names'     => 'required|array',
                'role_names.*'   => 'string',
                'account_type'   => 'required|in:staff,patient',
                'linked_staff_id'=> 'nullable|string',
                'linked_patient_id'=> 'nullable|string',
                'status'         => 'nullable|in:active,inactive',
            ]);

            // nếu muốn tự hash từ plaintext:
            // if (!empty($data['password'])) {
            //     $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            //     unset($data['password']);
            // }

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
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Cập nhật người dùng",
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
