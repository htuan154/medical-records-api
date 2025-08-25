<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\DoctorService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class DoctorController extends Controller
{
    public function __construct(private DoctorService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /** GET /api/v1/doctors?limit=&skip=&q=&specialty= */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'q'         => trim((string) $req->query('q', '')),
                'specialty' => $req->query('specialty'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** GET /api/v1/doctors/{id} */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/doctors */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                                   => 'sometimes|string',
                'type'                                  => 'sometimes|in:doctor',
                'personal_info.full_name'               => 'required|string',
                'personal_info.birth_date'              => 'nullable|date',
                'personal_info.gender'                  => 'nullable|in:male,female,other',
                'personal_info.phone'                   => 'nullable|string',
                'personal_info.email'                   => 'nullable|email',
                'professional_info.license_number'      => 'nullable|string',
                'professional_info.specialty'           => 'nullable|string',
                'professional_info.sub_specialties'     => 'nullable|array',
                'professional_info.experience_years'    => 'nullable|integer',
                'professional_info.education'           => 'nullable|array',
                'professional_info.certifications'      => 'nullable|array',
                'schedule.working_days'                 => 'nullable|array',
                'schedule.working_hours.start'          => 'nullable|string',
                'schedule.working_hours.end'            => 'nullable|string',
                'schedule.break_time.start'             => 'nullable|string',
                'schedule.break_time.end'               => 'nullable|string',
                'status'                                => 'nullable|in:active,inactive',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
    }
        catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** PUT /api/v1/doctors/{id} */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /** DELETE /api/v1/doctors/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
