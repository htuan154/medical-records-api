<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\RoleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;
use OpenApi\Annotations as OA;
/**
 * @OA\Tag(
 *     name="Roles",
 *     description="API endpoints for managing roles"
 * )
 */
class RoleController extends Controller
{
    public function __construct(private RoleService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }
    /**
     * @OA\Get(
     *     path="/api/v1/roles",
     *     tags={"Roles"},
     *     summary="Danh sách vai trò",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="name", in="query", @OA\Schema(type="string", description="Tìm kiếm theo tên vai trò")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive"})),
     *     @OA\Parameter(name="permission", in="query", @OA\Schema(type="string", description="Lọc theo quyền")),
     *     @OA\Response(response=200, description="Danh sách vai trò"),
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
                'name'       => $req->query('name'),
                'status'     => $req->query('status'),
                'permission' => $req->query('permission'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Chi tiết vai trò",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin vai trò"),
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
     *     path="/api/v1/roles",
     *     tags={"Roles"},
     *     summary="Tạo vai trò mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="admin"),
     *             @OA\Property(property="display_name", type="string", example="Quản trị viên"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"user.create", "user.read", "user.update", "user.delete"}),
     *             @OA\Property(property="description", type="string", example="Vai trò quản trị hệ thống"),
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
                '_id'          => 'sometimes|string',
                'type'         => 'sometimes|in:role',
                'name'         => 'required|string',
                'display_name' => 'nullable|string',
                'permissions'  => 'required|array',
                'permissions.*'=> 'string',
                'description'  => 'nullable|string',
                'status'       => 'nullable|in:active,inactive',
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
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Cập nhật vai trò",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="name", type="string", example="manager"),
     *             @OA\Property(property="display_name", type="string", example="Quản lý"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
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
     *     path="/api/v1/roles/{id}",
     *     tags={"Roles"},
     *     summary="Xóa vai trò",
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
