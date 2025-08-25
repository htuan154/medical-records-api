<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\MedicalRecordService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class MedicalRecordController extends Controller
{
    public function __construct(private MedicalRecordService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * GET /api/v1/medical-records
     * Params: limit, skip, patient_id, doctor_id, appointment_id, start, end, primary_icd, status
     */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'patient_id'     => $req->query('patient_id'),
                'doctor_id'      => $req->query('doctor_id'),
                'appointment_id' => $req->query('appointment_id'),
                'start'          => $req->query('start'),
                'end'            => $req->query('end'),
                'primary_icd'    => $req->query('primary_icd'),
                'status'         => $req->query('status'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** GET /api/v1/medical-records/{id} */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/medical-records */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                                   => 'sometimes|string',
                'type'                                  => 'sometimes|in:medical_record',
                'patient_id'                            => 'required|string',
                'doctor_id'                             => 'required|string',
                'visit_info.visit_date'                 => 'required|date',
                'visit_info.visit_type'                 => 'nullable|string',
                'visit_info.chief_complaint'            => 'nullable|string',
                'visit_info.appointment_id'             => 'nullable|string',
                'examination'                            => 'nullable|array',
                'diagnosis'                              => 'nullable|array',
                'treatment_plan'                         => 'nullable|array',
                'attachments'                            => 'nullable|array',
                'status'                                 => 'nullable|in:scheduled,completed,cancelled',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** PUT /api/v1/medical-records/{id} (cần _rev) */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /** DELETE /api/v1/medical-records/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
