<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\RoleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class RoleController extends Controller
{
    public function __construct(private RoleService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * GET /api/v1/roles
     * Params: limit, skip, name, status, permission
     */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'name'       => $req->query('name'),
                'status'     => $req->query('status'),
                'permission' => $req->query('permission'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** GET /api/v1/roles/{id} */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/roles */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'          => 'sometimes|string',
                'type'         => 'sometimes|in:role',
                'name'         => 'required|string',
                'display_name' => 'nullable|string',
                'permissions'  => 'required|array',
                'permissions.*'=> 'string',
                'description'  => 'nullable|string',
                'status'       => 'nullable|in:active,inactive',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** PUT /api/v1/roles/{id} (cần _rev) */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /** DELETE /api/v1/roles/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
