<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\ConsultationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Consultations",
 *     description="API endpoints for customer consultation chat"
 * )
 */
class ConsultationController extends Controller
{
    public function __construct(private ConsultationService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/consultations",
     *     tags={"Consultations"},
     *     summary="Danh sach phien tu van",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", required=false, @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="patient_id", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="staff_id", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"waiting","active","closed"})),
     *     @OA\Parameter(name="active", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index(Request $req)
    {
        try {
            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'patient_id' => $req->query('patient_id'),
                'staff_id'   => $req->query('staff_id'),
                'status'     => $req->query('status'),
                'active'     => $req->query('active'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/consultations/{id}",
     *     tags={"Consultations"},
     *     summary="Chi tiet phien tu van",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show(string $id)
    {
        try {
            return response()->json($this->svc->get($id), 200);
        } catch (Throwable $e) {
            $code = (str_contains($e->getMessage(), 'not_found') || str_contains($e->getMessage(), 'missing')) ? 404 : 500;
            return $this->error($e, $code);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/consultations",
     *     tags={"Consultations"},
     *     summary="Tao phien tu van moi",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id","patient_info"},
     *             @OA\Property(property="patient_id", type="string", example="patient_2024_001"),
     *             @OA\Property(
     *                 property="patient_info",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nguyen Van A"),
     *                 @OA\Property(property="phone", type="string", example="0901234567"),
     *                 @OA\Property(property="avatar", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $req)
    {
        try {
            $validated = $req->validate([
                '_id' => 'nullable|string',
                'patient_id'   => 'required|string',
                'patient_info' => 'required|array',
                'patient_info.name'  => 'required|string',
                'patient_info.phone' => 'required|string',
                'patient_info.avatar' => 'nullable|string',
            ]);
            // Nếu có _id thì truyền xuống service, nếu không có thì service tự sinh
            $data = $validated;
            if ($req->has('_id')) {
                $data['_id'] = $req->input('_id');
            }
            return response()->json($this->svc->create($data), 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/consultations/{id}",
     *     tags={"Consultations"},
     *     summary="Cap nhat phien tu van",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"waiting","active","closed"}),
     *             @OA\Property(property="last_message", type="string"),
     *             @OA\Property(property="unread_count_patient", type="integer"),
     *             @OA\Property(property="unread_count_staff", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $req, string $id)
    {
        try {
            $data = $req->only([
                'status', 'last_message', 'last_message_at',
                'unread_count_patient', 'unread_count_staff',
                'staff_id', 'staff_info'
            ]);

            return response()->json($this->svc->update($id, $data), 200);
        } catch (Throwable $e) {
            $code = (str_contains($e->getMessage(), 'not_found') || str_contains($e->getMessage(), 'missing')) ? 404 : 500;
            return $this->error($e, $code);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/consultations/{id}",
     *     tags={"Consultations"},
     *     summary="Xoa phien tu van",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="rev", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy(Request $req, string $id)
    {
        try {
            $rev = $req->query('rev');
            if (!$rev) {
                return response()->json(['error' => 'Missing rev parameter'], 400);
            }
            return response()->json($this->svc->delete($id, $rev), 200);
        } catch (Throwable $e) {
            $code = (str_contains($e->getMessage(), 'not_found') || str_contains($e->getMessage(), 'missing')) ? 404 : 500;
            return $this->error($e, $code);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/consultations/{id}/assign",
     *     tags={"Consultations"},
     *     summary="Nhan vien nhan phien tu van",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"staff_id","staff_info"},
     *             @OA\Property(property="staff_id", type="string", example="staff_2025_001"),
     *             @OA\Property(
     *                 property="staff_info",
     *                 type="object",
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="staff_type", type="string"),
     *                 @OA\Property(property="avatar", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function assign(Request $req, string $id)
    {
        try {
            $validated = $req->validate([
                'staff_id'   => 'required|string',
                'staff_info' => 'required|array',
            ]);

            return response()->json(
                $this->svc->assignStaff($id, $validated['staff_id'], $validated['staff_info']),
                200
            );
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            $code = (str_contains($e->getMessage(), 'not_found') || str_contains($e->getMessage(), 'missing')) ? 404 : 500;
            return $this->error($e, $code);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/consultations/{id}/close",
     *     tags={"Consultations"},
     *     summary="Dong phien tu van",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function close(string $id)
    {
        try {
            return response()->json($this->svc->close($id), 200);
        } catch (Throwable $e) {
            $code = (str_contains($e->getMessage(), 'not_found') || str_contains($e->getMessage(), 'missing')) ? 404 : 500;
            return $this->error($e, $code);
        }
    }
}
