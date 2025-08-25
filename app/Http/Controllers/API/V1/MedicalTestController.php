<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\MedicalTestService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class MedicalTestController extends Controller
{
    public function __construct(private MedicalTestService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * GET /api/v1/medical-tests
     * Params: limit, skip, patient_id, doctor_id, medical_record_id, test_type, start, end, status
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
                'test_type'        => $req->query('test_type'),
                'start'            => $req->query('start'),
                'end'              => $req->query('end'),
                'status'           => $req->query('status'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** GET /api/v1/medical-tests/{id} */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/medical-tests */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                         => 'sometimes|string',
                'type'                        => 'sometimes|in:medical_test',
                'patient_id'                  => 'required|string',
                'doctor_id'                   => 'required|string',
                'medical_record_id'           => 'nullable|string',
                'test_info.test_type'         => 'required|string',
                'test_info.test_name'         => 'nullable|string',
                'test_info.ordered_date'      => 'required|date',
                'test_info.sample_collected_date' => 'nullable|date',
                'test_info.result_date'       => 'nullable|date',
                'results'                     => 'nullable|array',
                'interpretation'              => 'nullable|string',
                'status'                      => 'nullable|string',
                'lab_technician'              => 'nullable|string',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** PUT /api/v1/medical-tests/{id} (cần _rev) */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /** DELETE /api/v1/medical-tests/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
