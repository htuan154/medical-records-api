<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\appointmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;
    
/**
 * @OA\Tag(
 *     name="Appointments",
 *     description="API endpoints for managing appointments"
 * )
 */
class AppointmentController extends Controller
{
    public function __construct(private appointmentService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/appointments",
     *     tags={"Appointments"},
     *     summary="Danh sách cuộc hẹn",
     *     description="Trả về danh sách các appointment, có thể lọc theo patient_id, doctor_id, start, end, status",
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Số lượng bản ghi trả về (mặc định: 50)",
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Parameter(
     *         name="skip",
     *         in="query",
     *         required=false,
     *         description="Số lượng bản ghi bỏ qua (mặc định: 0)",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="patient_id",
     *         in="query",
     *         required=false,
     *         description="ID của bệnh nhân",
     *         @OA\Schema(type="string", example="patient_123")
     *     ),
     *     @OA\Parameter(
     *         name="doctor_id",
     *         in="query",
     *         required=false,
     *         description="ID của bác sĩ",
     *         @OA\Schema(type="string", example="doctor_456")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Thời gian bắt đầu lọc",
     *         @OA\Schema(type="string", format="date-time", example="2024-01-01T00:00:00Z")
     *     ),
     *     @OA\Parameter(
     *         name="end",
     *         in="query",
     *         required=false,
     *         description="Thời gian kết thúc lọc",
     *         @OA\Schema(type="string", format="date-time", example="2024-12-31T23:59:59Z")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Trạng thái cuộc hẹn",
     *         @OA\Schema(type="string", example="scheduled")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách appointments thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_rows", type="integer", example=100),
     *             @OA\Property(property="offset", type="integer", example=0),
     *             @OA\Property(
     *                 property="rows",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="_id", type="string", example="appointment_123"),
     *                     @OA\Property(property="patient_id", type="string", example="patient_123"),
     *                     @OA\Property(property="doctor_id", type="string", example="doctor_456"),
     *                     @OA\Property(property="status", type="string", example="scheduled")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Exception"),
     *             @OA\Property(property="message", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'patient_id' => $req->query('patient_id'),
                'doctor_id'  => $req->query('doctor_id'),
                'start'      => $req->query('start'),
                'end'        => $req->query('end'),
                'status'     => $req->query('status'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/appointments/{id}",
     *     tags={"Appointments"},
     *     summary="Lấy thông tin chi tiết cuộc hẹn",
     *     description="Trả về thông tin chi tiết của một appointment theo ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của appointment",
     *         @OA\Schema(type="string", example="appointment_123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin appointment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="_id", type="string", example="appointment_123"),
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="type", type="string", example="appointment"),
     *             @OA\Property(property="patient_id", type="string", example="patient_123"),
     *             @OA\Property(property="doctor_id", type="string", example="doctor_456"),
     *             @OA\Property(
     *                 property="appointment_info",
     *                 type="object",
     *                 @OA\Property(property="scheduled_date", type="string", format="date", example="2024-01-15"),
     *                 @OA\Property(property="duration", type="integer", example=30),
     *                 @OA\Property(property="type", type="string", example="consultation"),
     *                 @OA\Property(property="priority", type="string", example="normal")
     *             ),
     *             @OA\Property(property="reason", type="string", example="Regular checkup"),
     *             @OA\Property(property="status", type="string", example="scheduled"),
     *             @OA\Property(property="notes", type="string", example="Patient notes"),
     *             @OA\Property(property="reminders", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="created_by", type="string", example="staff_789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy appointment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="not_found"),
     *             @OA\Property(property="message", type="string", example="Appointment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Exception"),
     *             @OA\Property(property="message", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/appointments",
     *     tags={"Appointments"},
     *     summary="Tạo cuộc hẹn mới",
     *     description="Tạo một appointment mới trong hệ thống",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="_id", type="string", example="appointment_123", description="ID tùy chọn"),
     *             @OA\Property(property="type", type="string", example="appointment", enum={"appointment"}),
     *             @OA\Property(property="patient_id", type="string", example="patient_123", description="ID bệnh nhân (bắt buộc)"),
     *             @OA\Property(property="doctor_id", type="string", example="doctor_456", description="ID bác sĩ (bắt buộc)"),
     *             @OA\Property(
     *                 property="appointment_info",
     *                 type="object",
     *                 @OA\Property(property="scheduled_date", type="string", format="date", example="2024-01-15", description="Ngày hẹn (bắt buộc)"),
     *                 @OA\Property(property="duration", type="integer", example=30, description="Thời gian (phút)"),
     *                 @OA\Property(property="type", type="string", example="consultation", description="Loại cuộc hẹn"),
     *                 @OA\Property(property="priority", type="string", example="normal", description="Độ ưu tiên")
     *             ),
     *             @OA\Property(property="reason", type="string", example="Regular checkup", description="Lý do khám"),
     *             @OA\Property(property="status", type="string", example="scheduled", description="Trạng thái"),
     *             @OA\Property(property="notes", type="string", example="Patient notes", description="Ghi chú"),
     *             @OA\Property(property="reminders", type="array", @OA\Items(type="string"), description="Danh sách nhắc nhở"),
     *             @OA\Property(property="created_by", type="string", example="staff_789", description="Người tạo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tạo appointment thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="id", type="string", example="appointment_123"),
     *             @OA\Property(property="rev", type="string", example="1-abc123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="bad_request"),
     *             @OA\Property(property="reason", type="string", example="Invalid data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Lỗi validation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="validation_error"),
     *             @OA\Property(
     *                 property="details",
     *                 type="object",
     *                 @OA\Property(
     *                     property="patient_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The patient_id field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Exception"),
     *             @OA\Property(property="message", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                               => 'sometimes|string',
                'type'                              => 'sometimes|in:appointment',
                'patient_id'                        => 'required|string',
                'doctor_id'                         => 'required|string',
                'appointment_info.scheduled_date'   => 'required|date',
                'appointment_info.duration'         => 'nullable|integer',
                'appointment_info.type'             => 'nullable|string',
                'appointment_info.priority'         => 'nullable|string',
                'reason'                            => 'nullable|string',
                'status'                            => 'nullable|string',
                'notes'                             => 'nullable|string',
                'reminders'                         => 'nullable|array',
                'created_by'                        => 'nullable|string',
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
     *     path="/api/v1/appointments/{id}",
     *     tags={"Appointments"},
     *     summary="Cập nhật thông tin cuộc hẹn",
     *     description="Cập nhật thông tin của một appointment theo ID (cần có _rev)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của appointment",
     *         @OA\Schema(type="string", example="appointment_123")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="_rev", type="string", example="1-abc123", description="Revision của document (bắt buộc)"),
     *             @OA\Property(property="patient_id", type="string", example="patient_123"),
     *             @OA\Property(property="doctor_id", type="string", example="doctor_456"),
     *             @OA\Property(
     *                 property="appointment_info",
     *                 type="object",
     *                 @OA\Property(property="scheduled_date", type="string", format="date", example="2024-01-15"),
     *                 @OA\Property(property="duration", type="integer", example=30),
     *                 @OA\Property(property="type", type="string", example="consultation"),
     *                 @OA\Property(property="priority", type="string", example="high")
     *             ),
     *             @OA\Property(property="reason", type="string", example="Follow-up checkup"),
     *             @OA\Property(property="status", type="string", example="confirmed"),
     *             @OA\Property(property="notes", type="string", example="Updated patient notes"),
     *             @OA\Property(property="reminders", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="updated_by", type="string", example="staff_789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="id", type="string", example="appointment_123"),
     *             @OA\Property(property="rev", type="string", example="2-def456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dữ liệu không hợp lệ hoặc thiếu _rev",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="bad_request"),
     *             @OA\Property(property="reason", type="string", example="Document update conflict")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy appointment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="not_found"),
     *             @OA\Property(property="reason", type="string", example="missing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Document đã được cập nhật bởi user khác",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="conflict"),
     *             @OA\Property(property="reason", type="string", example="Document update conflict")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Exception"),
     *             @OA\Property(property="message", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/appointments/{id}",
     *     tags={"Appointments"},
     *     summary="Xóa cuộc hẹn",
     *     description="Xóa một appointment theo ID (cần có rev parameter)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của appointment",
     *         @OA\Schema(type="string", example="appointment_123")
     *     ),
     *     @OA\Parameter(
     *         name="rev",
     *         in="query",
     *         required=true,
     *         description="Revision của document",
     *         @OA\Schema(type="string", example="1-abc123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="id", type="string", example="appointment_123"),
     *             @OA\Property(property="rev", type="string", example="2-def456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Thiếu rev parameter",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="bad_request"),
     *             @OA\Property(property="reason", type="string", example="Missing rev parameter")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy appointment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="not_found"),
     *             @OA\Property(property="reason", type="string", example="missing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Document đã được cập nhật",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="conflict"),
     *             @OA\Property(property="reason", type="string", example="Document update conflict")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Exception"),
     *             @OA\Property(property="message", type="string", example="Database connection failed")
     *         )
     *     )
     * )
     */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
