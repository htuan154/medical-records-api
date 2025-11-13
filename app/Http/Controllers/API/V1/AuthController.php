<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Repositories\Interfaces\UsersRepositoryInterface;
use App\Repositories\Interfaces\RolesRepositoryInterface;
use App\Services\Auth\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="linked_patient_id", type="string", description="ID bệnh nhân liên kết (nếu có)"),
     *                 @OA\Property(property="linked_staff_id", type="string", description="ID nhân viên liên kết (nếu có)"),
     *                 @OA\Property(property="linked_doctor_id", type="string", description="ID bác sĩ liên kết (nếu có)")
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

            // Build user response with linked entity ID
            $userResponse = [
                'id' => $user['_id'],
                'username' => $user['username'] ?? null,
                'role_names' => $user['role_names'] ?? [],
                'permissions' => $perms,
                'status' => $user['status'] ?? null,
            ];

            // Add linked entity ID if exists
            if (!empty($user['linked_patient_id'])) {
                $userResponse['linked_patient_id'] = $user['linked_patient_id'];
            }
            if (!empty($user['linked_staff_id'])) {
                $userResponse['linked_staff_id'] = $user['linked_staff_id'];
            }
            if (!empty($user['linked_doctor_id'])) {
                $userResponse['linked_doctor_id'] = $user['linked_doctor_id'];
            }

            return response()->json(array_merge([
                'ok' => true,
                'user' => $userResponse,
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
     * @OA\Post(
     *     path="/api/v1/logout",
     *     tags={"Auth"},
     *     summary="Đăng xuất",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Đăng xuất thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function logout(Request $req)
    {
        // For JWT tokens, we typically don't maintain server-side sessions
        // The token will naturally expire based on its TTL
        // Client should discard the token on their side
        
        return response()->json([
            'ok' => true,
            'message' => 'Logged out successfully'
        ], 200);
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
        try {
            $auth = $req->attributes->get('auth');
            // Đảm bảo $auth là array
            if (is_object($auth)) {
                $auth = json_decode(json_encode($auth), true);
            }
            $userId = $auth['sub'] ?? null;
            
            if (!$userId) {
                return response()->json(['error' => 'invalid_token'], 401);
            }
            
            // Lấy user đầy đủ từ database (bao gồm _id và _rev)
            $user = $this->users->get($userId);
            
            // Đảm bảo $user là array
            if (is_object($user)) {
                $user = json_decode(json_encode($user), true);
            }
            
            if (isset($user['error'])) {
                return response()->json(['error' => 'user_not_found'], 404);
            }
            
            return response()->json([
                'ok' => true,
                'user' => $user,  // Trả về user đầy đủ với _id và _rev
                'token' => $auth  // Giữ lại token decoded để tương thích với code cũ
            ], 200);
        } catch (Throwable $e) {
            return response()->json(['error' => 'server_error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/auth/profile",
     *     tags={"Auth"},
     *     summary="Cập nhật thông tin cá nhân",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="phone", type="string", example="0123456789"),
     *             @OA\Property(property="address", type="string", example="123 Đường ABC, Quận XYZ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật thông tin thành công"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function updateProfile(UpdateProfileRequest $req)
    {
        try {
            $auth = $req->attributes->get('auth');
            $userId = $auth['sub'] ?? null;
            
            if (!$userId) {
                return response()->json(['error' => 'invalid_token'], 401);
            }

            $data = $req->validated();
            
            // Lấy user hiện tại
            $user = $this->users->get($userId);
            if (isset($user['error'])) {
                return response()->json(['error' => 'user_not_found'], 404);
            }

            // Cập nhật thông tin
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'updated_at' => now()->toIso8601String()
            ];

            $result = $this->users->update($userId, $user['_rev'], $updateData);
            
            if (isset($result['error'])) {
                return response()->json(['error' => 'update_failed', 'message' => $result['error']], 500);
            }

            // Lấy user đã cập nhật
            $updatedUser = $this->users->get($userId);

            return response()->json([
                'ok' => true,
                'message' => 'Cập nhật thông tin thành công',
                'user' => [
                    'id' => $updatedUser['_id'],
                    'name' => $updatedUser['name'],
                    'email' => $updatedUser['email'],
                    'phone' => $updatedUser['phone'] ?? null,
                    'address' => $updatedUser['address'] ?? null,
                    'username' => $updatedUser['username'],
                    'role_names' => $updatedUser['role_names'] ?? [],
                    'updated_at' => $updatedUser['updated_at']
                ]
            ], 200);

        } catch (Throwable $e) {
            return response()->json(['error' => 'server_error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/change-password",
     *     tags={"Auth"},
     *     summary="Đổi mật khẩu",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="current_password", type="string", example="old_password"),
     *             @OA\Property(property="new_password", type="string", example="new_password123"),
     *             @OA\Property(property="new_password_confirmation", type="string", example="new_password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đổi mật khẩu thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đổi mật khẩu thành công")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=401, description="Mật khẩu hiện tại không đúng"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function changePassword(ChangePasswordRequest $req)
    {
        try {
            Log::info('Change password attempt started');
            
            $auth = $req->attributes->get('auth');
            $userId = $auth['sub'] ?? null;
            
            Log::info('User ID from JWT', ['user_id' => $userId]);
            
            if (!$userId) {
                return response()->json(['error' => 'invalid_token'], 401);
            }

            $data = $req->validated();
            Log::info('Validated data', ['data' => array_keys($data)]);
            
            // Lấy user hiện tại giống như UserController
            $user = $this->users->get($userId);
            if (isset($user['error'])) {
                Log::error('User not found', ['user_id' => $userId, 'error' => $user['error']]);
                return response()->json(['error' => 'user_not_found'], 404);
            }
            
            Log::info('User found', ['user_id' => $userId, 'has_password_hash' => isset($user['password_hash'])]);

            // Kiểm tra mật khẩu hiện tại
            if (!password_verify($data['current_password'], $user['password_hash'] ?? '')) {
                Log::warning('Invalid current password', ['user_id' => $userId]);
                return response()->json([
                    'error' => 'invalid_current_password',
                    'message' => 'Mật khẩu hiện tại không đúng'
                ], 401);
            }

            // Chuẩn bị data update giống UserController pattern  
            $updateData = [
                '_rev' => $user['_rev'], // Quan trọng: phải có _rev
                'password_hash' => password_hash($data['new_password'], PASSWORD_BCRYPT), // Dùng BCRYPT như UserController
                'updated_at' => now()->toIso8601String()
            ];

            // Merge với user hiện tại để giữ nguyên các field khác
            $fullUpdateData = array_merge($user, $updateData);

            Log::info('Attempting to update password', ['user_id' => $userId, 'rev' => $user['_rev']]);
            
            // Dùng update method giống UserController - chỉ 2 parameters
            $result = $this->users->update($userId, $fullUpdateData);
            
            if (isset($result['error'])) {
                Log::error('Update failed', ['error' => $result['error']]);
                return response()->json(['error' => 'update_failed', 'message' => $result['error']], 500);
            }

            Log::info('Password changed successfully', ['user_id' => $userId]);

            return response()->json([
                'ok' => true,
                'message' => 'Đổi mật khẩu thành công'
            ], 200);

        } catch (Throwable $e) {
            Log::error('Change password error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'server_error', 'message' => $e->getMessage()], 500);
        }
    }
}
