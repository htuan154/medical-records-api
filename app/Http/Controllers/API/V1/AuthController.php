<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Repositories\Interfaces\UsersRepositoryInterface;
use App\Repositories\Interfaces\RolesRepositoryInterface;
use App\Services\Auth\JwtService;
use Illuminate\Http\Request;
use Throwable;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication endpoints"
 * )
 */
class AuthController extends Controller
{
    public function __construct(
        private UsersRepositoryInterface $users,
        private RolesRepositoryInterface $roles,
        private JwtService $jwt
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     tags={"Auth"},
     *     summary="Đăng nhập",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="username", type="string", example="admin"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đăng nhập thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="role_names", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="status", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Thông tin đăng nhập không đúng"),
     *     @OA\Response(response=403, description="Tài khoản bị khóa"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function login(LoginRequest $req)
    {
        try {
            $data = $req->validated();

            // tìm user theo username qua view users/by_username
            $rs = $this->users->byUsername($data['username'], 1, 0);
            $user = $rs['rows'][0]['doc'] ?? null;
            if (!$user) return response()->json(['error'=>'invalid_credentials'], 401);
            if (($user['status'] ?? 'inactive') !== 'active') {
                return response()->json(['error'=>'inactive_user'], 403);
            }

            // bcrypt verify
            if (!password_verify($data['password'], $user['password_hash'] ?? '')) {
                return response()->json(['error'=>'invalid_credentials'], 401);
            }

            // gom permissions từ role_names
            $perms = [];
            foreach (($user['role_names'] ?? []) as $r) {
                $doc = $this->roles->byName($r)['rows'][0]['doc'] ?? null;
                if ($doc && !empty($doc['permissions'])) {
                    $perms = array_merge($perms, $doc['permissions']);
                }
            }
            $perms = array_values(array_unique($perms));

            $tokens = $this->jwt->issueTokens($user, $perms);

            return response()->json(array_merge([
    'ok' => true,
    'user' => [
        'id' => $user['_id'],
        'username' => $user['username'] ?? null,
        'role_names' => $user['role_names'] ?? [],
        'permissions' => $perms,
        'status' => $user['status'] ?? null,
    ],
], $tokens), 200);
        } catch (Throwable $e) {
            return response()->json(['error'=>'server_error','message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/refresh",
     *     tags={"Auth"},
     *     summary="Refresh token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="refresh_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Refresh thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Token không hợp lệ"),
     *     @OA\Response(response=401, description="Token hết hạn"),
     *     @OA\Response(response=403, description="Tài khoản bị khóa"),
     *     @OA\Response(response=404, description="User không tồn tại")
     * )
     */
    public function refresh(Request $req)
    {
        $refresh = (string) $req->input('refresh_token', '');
        if (!$refresh) return response()->json(['error'=>'invalid_request','message'=>'Missing refresh_token'], 400);

        try {
            $decoded = $this->jwt->verify($refresh);
            if (($decoded->typ ?? '') !== 'refresh') {
                return response()->json(['error'=>'invalid_token_type'], 400);
            }

            $userId = $decoded->sub ?? null;
            if (!$userId) return response()->json(['error'=>'invalid_token_payload'], 400);

            $user = $this->users->get($userId);
            if (isset($user['error'])) return response()->json(['error'=>'user_not_found'], 404);
            if (($user['status'] ?? 'inactive') !== 'active') return response()->json(['error'=>'inactive_user'], 403);

            $perms = [];
            foreach (($user['role_names'] ?? []) as $r) {
                $doc = $this->roles->byName($r)['rows'][0]['doc'] ?? null;
                if ($doc && !empty($doc['permissions'])) $perms = array_merge($perms, $doc['permissions']);
            }
            $tokens = $this->jwt->issueTokens($user, array_values(array_unique($perms)));

            return response()->json(array_merge(['ok'=>true], $tokens), 200);
        } catch (Throwable $e) {
            return response()->json(['error'=>'unauthorized','message'=>$e->getMessage()], 401);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/me",
     *     tags={"Auth"},
     *     summary="Thông tin user hiện tại",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin user",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="token", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function me(Request $req)
    {
        return response()->json([
            'ok' => true,
            'token' => $req->attributes->get('auth'),
        ], 200);
    }
}
