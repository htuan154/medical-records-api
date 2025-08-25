<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    public function __construct(private UserService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /** GET /api/v1/users?limit=&skip=&username=&staff_id=&patient_id= */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'username'   => $req->query('username'),
                'staff_id'   => $req->query('staff_id'),
                'patient_id' => $req->query('patient_id'),
            ];
            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** GET /api/v1/users/{id} */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/users */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'            => 'sometimes|string',
                'type'           => 'sometimes|in:user',
                'username'       => 'required|string',
                'email'          => 'nullable|email',
                'password_hash'  => 'required|string', // gửi hash sẵn (bcrypt)
                'role_names'     => 'required|array',
                'role_names.*'   => 'string',
                'account_type'   => 'required|in:staff,patient',
                'linked_staff_id'=> 'nullable|string',
                'linked_patient_id'=> 'nullable|string',
                'status'         => 'nullable|in:active,inactive',
            ]);

            // nếu muốn tự hash từ plaintext:
            // if (!empty($data['password'])) {
            //     $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            //     unset($data['password']);
            // }

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** PUT /api/v1/users/{id} */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /** DELETE /api/v1/users/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
