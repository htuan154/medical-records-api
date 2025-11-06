<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\MessageService;
use App\Services\CouchDB\ConsultationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Messages",
 *     description="API endpoints for consultation chat messages"
 * )
 */
class MessageController extends Controller
{
    public function __construct(
        private MessageService $svc,
        private ConsultationService $consultationSvc
    ) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/messages",
     *     tags={"Messages"},
     *     summary="Danh sach tin nhan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", example=100)),
     *     @OA\Parameter(name="skip", in="query", required=false, @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="consultation_id", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="sender_id", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index(Request $req)
    {
        try {
            $limit = (int) $req->query('limit', 100);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'consultation_id' => $req->query('consultation_id'),
                'sender_id'       => $req->query('sender_id'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/messages/{id}",
     *     tags={"Messages"},
     *     summary="Chi tiet tin nhan",
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
     *     path="/api/v1/messages",
     *     tags={"Messages"},
     *     summary="Gui tin nhan moi",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"consultation_id","sender_id","sender_type","sender_name","message"},
     *             @OA\Property(property="consultation_id", type="string", example="consultation_2025_001"),
     *             @OA\Property(property="sender_id", type="string", example="patient_2024_001"),
     *             @OA\Property(property="sender_type", type="string", enum={"patient","staff"}, example="patient"),
     *             @OA\Property(property="sender_name", type="string", example="Nguyen Van A"),
     *             @OA\Property(property="message", type="string", example="Xin chao benh vien")
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
                'consultation_id' => 'required|string',
                'sender_id'       => 'required|string',
                'sender_type'     => 'required|string|in:patient,staff',
                'sender_name'     => 'required|string',
                'message'         => 'required|string',
            ]);

            // Create message
            $result = $this->svc->create($validated);

            // Update consultation's last_message and increment unread count
            $timestamp = $result['created_at'] ?? now()->toIso8601String();
            $this->consultationSvc->updateLastMessage(
                $validated['consultation_id'],
                $validated['message'],
                $timestamp
            );

            // Increment unread count for the receiver
            $consultation = $this->consultationSvc->get($validated['consultation_id']);
            $receiverType = $validated['sender_type'] === 'patient' ? 'staff' : 'patient';
            $currentUnread = $consultation['unread_count_' . $receiverType] ?? 0;
            $this->consultationSvc->updateUnreadCount(
                $validated['consultation_id'],
                $receiverType,
                $currentUnread + 1
            );

            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/messages/{id}",
     *     tags={"Messages"},
     *     summary="Cap nhat tin nhan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="is_read", type="boolean", example=true)
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
            $data = $req->only(['is_read']);
            return response()->json($this->svc->update($id, $data), 200);
        } catch (Throwable $e) {
            $code = (str_contains($e->getMessage(), 'not_found') || str_contains($e->getMessage(), 'missing')) ? 404 : 500;
            return $this->error($e, $code);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/messages/{id}",
     *     tags={"Messages"},
     *     summary="Xoa tin nhan",
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
     *     path="/api/v1/messages/mark-read",
     *     tags={"Messages"},
     *     summary="Danh dau da doc nhieu tin nhan",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message_ids"},
     *             @OA\Property(
     *                 property="message_ids",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"message_001","message_002"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function markAsRead(Request $req)
    {
        try {
            $validated = $req->validate([
                'message_ids' => 'required|array',
                'message_ids.*' => 'string',
            ]);

            return response()->json(
                $this->svc->markMultipleAsRead($validated['message_ids']),
                200
            );
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/consultations/{consultationId}/messages",
     *     tags={"Messages"},
     *     summary="Lay tat ca tin nhan cua mot phien tu van",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="consultationId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", example=100)),
     *     @OA\Parameter(name="skip", in="query", required=false, @OA\Schema(type="integer", example=0)),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function byConsultation(Request $req, string $consultationId)
    {
        try {
            $limit = (int) $req->query('limit', 100);
            $skip  = (int) $req->query('skip', 0);

            return response()->json(
                $this->svc->getByConsultation($consultationId, $limit, $skip),
                200
            );
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }
}
