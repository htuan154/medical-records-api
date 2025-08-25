<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\PatientService;   // <-- đúng namespace
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class PatientController extends Controller
{
    public function __construct(private PatientService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /** GET /api/v1/patients?limit=&skip=&q= */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $q     = trim((string) $req->query('q', ''));

            return response()->json($this->svc->list($limit, $skip, $q), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** GET /api/v1/patients/{id} */
    public function show(string $id)
    {
        try {
            $res = $this->svc->find($id);
            return response()->json($res['data'], $res['status']);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** POST /api/v1/patients */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                       => 'sometimes|string',
                'type'                      => 'sometimes|in:patient',
                'personal_info.full_name'   => 'required|string',
                'personal_info.gender'      => 'nullable|string',
                'personal_info.phone'       => 'nullable|string',
                'personal_info.birth_date'  => 'nullable|date',
                'personal_info.id_number'   => 'nullable|string',
                'medical_info'              => 'nullable|array',
                'address'                   => 'nullable|array',
                'status'                    => 'nullable|in:active,inactive',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** PUT /api/v1/patients/{id} */
    public function update(Request $req, string $id)
    {
        try {
            $res = $this->svc->update($id, $req->all());
            return response()->json($res['data'], $res['status']);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** DELETE /api/v1/patients/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        try {
            $rev = $req->query('rev') ?? $req->input('rev');
            $res = $this->svc->delete($id, $rev);
            return response()->json($res['data'], $res['status']);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }
}
