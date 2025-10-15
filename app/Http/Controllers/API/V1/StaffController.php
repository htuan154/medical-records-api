<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\StaffService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Staff",
 *     description="API endpoints for managing staff"
 * )
 */
class StaffController extends Controller
{
    public function __construct(private StaffService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/staffs",
     *     tags={"Staff"},
     *     summary="Danh sách nhân viên",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="staff_type", in="query", @OA\Schema(type="string", description="Loại nhân viên")),
     *     @OA\Parameter(name="department", in="query", @OA\Schema(type="string", description="Phòng ban")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive"})),
     *     @OA\Parameter(name="day", in="query", @OA\Schema(type="string", description="Lọc theo ngày làm việc")),
     *     @OA\Response(response=200, description="Danh sách nhân viên"),
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
                'staff_type' => $req->query('staff_type'),
                'department' => $req->query('department'),
                'status'     => $req->query('status'),
                'day'        => $req->query('day'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/staffs/{id}",
     *     tags={"Staff"},
     *     summary="Chi tiết nhân viên",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin nhân viên"),
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
     *     path="/api/v1/staffs",
     *     tags={"Staff"},
     *     summary="Tạo nhân viên mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="full_name", type="string", example="Nguyễn Thị B"),
     *             @OA\Property(property="staff_type", type="string", example="doctor"),
     *             @OA\Property(property="gender", type="string", example="Nữ"),
     *             @OA\Property(property="phone", type="string", example="0123456789"),
     *             @OA\Property(property="email", type="string", format="email", example="doctor@hospital.com"),
     *             @OA\Property(property="department", type="string", example="Khoa Nội"),
     *             @OA\Property(
     *                 property="shift",
     *                 type="object",
     *                 @OA\Property(property="days", type="array", @OA\Items(type="string"), example={"monday", "tuesday", "wednesday"}),
     *                 @OA\Property(property="start", type="string", example="08:00"),
     *                 @OA\Property(property="end", type="string", example="17:00")
     *             ),
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
                '_id'        => 'sometimes|string',
                'type'       => 'sometimes|in:staff',
                'full_name'  => 'required|string|min:2|max:255',
                'staff_type' => 'required|string|min:2|max:100',
                'gender'     => 'nullable|string|in:Nam,Nữ,Khác,male,female,other',
                'phone'      => 'nullable|string|regex:/^[0-9+\-\s()]+$/|max:20',
                'email'      => 'nullable|email|max:255',
                'department' => 'nullable|string|max:255',
                'shift'      => 'nullable|array',
                'shift.days' => 'nullable|array',
                'shift.days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday,mon,tue,wed,thu,fri,sat,sun',
                // Dùng date_format để tránh lỗi regex khi sử dụng ký tự '|' trong pattern
                'shift.start'=> 'nullable|string|date_format:H:i',
                'shift.end'  => 'nullable|string|date_format:H:i',
                'status'     => 'nullable|in:active,inactive',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json([
                'error' => 'validation_error', 
                'message' => 'Dữ liệu không hợp lệ', 
                'details' => $ve->errors(),
                'rules_info' => [
                    'full_name' => 'Bắt buộc, tối thiểu 2 ký tự',
                    'staff_type' => 'Bắt buộc, ví dụ: doctor, nurse, admin',
                    'gender' => 'Tùy chọn: Nam, Nữ, Khác hoặc male, female, other',
                    'phone' => 'Tùy chọn, chỉ chấp nhận số và ký tự đặc biệt',
                    'shift.days' => 'Tùy chọn: monday/mon, tuesday/tue, etc.',
                    'status' => 'Tùy chọn: active hoặc inactive'
                ]
            ], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/staffs/{id}",
     *     tags={"Staff"},
     *     summary="Cập nhật nhân viên",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="full_name", type="string", example="Trần Văn C"),
     *             @OA\Property(property="department", type="string", example="Khoa Ngoại"),
     *             @OA\Property(property="phone", type="string", example="0987654321"),
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
     *     path="/api/v1/staffs/{id}",
     *     tags={"Staff"},
     *     summary="Xóa nhân viên",
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
                    return response()->json(['error' => 'not_found', 'message' => 'Staff not found'], 404);
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
                'message' => 'Staff deleted successfully'
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
                        'message' => 'Staff deleted successfully (retry)'
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
