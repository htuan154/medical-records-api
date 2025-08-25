<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\TreatmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class TreatmentController extends Controller
{
    public function __construct(private TreatmentService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * GET /api/v1/treatments
     * Params: limit, skip, patient_id, doctor_id, medical_record_id,
     *         status, treatment_type, medication_id, start, end (start_date)
     */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'patient_id'       => $req->query('patient_id'),
                'doctor_id'        => $req->query('doctor_id'),
                'medical_record_id'=> $req->query('medical_record_id'),
                'status'           => $req->query('status'),
                'treatment_type'   => $req->query('treatment_type'),
                'medication_id'    => $req->query('medication_id'),
                'start'            => $req->query('start'),
                'end'              => $req->query('end'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** GET /api/v1/treatments/{id} */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/treatments */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                           => 'sometimes|string',
                'type'                          => 'sometimes|in:treatment',
                'patient_id'                    => 'required|string',
                'doctor_id'                     => 'required|string',
                'medical_record_id'             => 'nullable|string',
                'treatment_info.treatment_name' => 'required|string',
                'treatment_info.start_date'     => 'required|date',
                'treatment_info.end_date'       => 'nullable|date',
                'treatment_info.duration_days'  => 'nullable|integer',
                'treatment_info.treatment_type' => 'nullable|string',
                'medications'                   => 'nullable|array',
                'medications.*.medication_id'   => 'nullable|string',
                'medications.*.name'            => 'nullable|string',
                'medications.*.dosage'          => 'nullable|string',
                'medications.*.frequency'       => 'nullable|string',
                'medications.*.route'           => 'nullable|string',
                'medications.*.instructions'    => 'nullable|string',
                'medications.*.quantity_prescribed' => 'nullable|numeric',
                'monitoring'                    => 'nullable|array',
                'status'                         => 'nullable|in:active,completed,paused,cancelled',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** PUT /api/v1/treatments/{id} (cần _rev) */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /** DELETE /api/v1/treatments/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
