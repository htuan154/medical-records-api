<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\appointmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class appointmentController extends Controller
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
     * GET /api/v1/appointments
     * Params:
     *  - limit, skip
     *  - patient_id, doctor_id
     *  - start, end (ISO 8601) để lọc theo scheduled_date
     *  - status (scheduled|completed|cancelled|...)
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

    /** GET /api/v1/appointments/{id} */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/appointments */
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

    /** PUT /api/v1/appointments/{id} (cần _rev) */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /** DELETE /api/v1/appointments/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
