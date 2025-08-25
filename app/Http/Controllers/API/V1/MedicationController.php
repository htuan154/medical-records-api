<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\MedicationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class MedicationController extends Controller
{
    public function __construct(private MedicationService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * GET /api/v1/medications
     * Params: limit, skip, q (name), barcode, class, start, end (expiry),
     *         status, low_stock (ngưỡng, ví dụ 20)
     */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'q'        => $req->query('q'),
                'barcode'  => $req->query('barcode'),
                'class'    => $req->query('class'),
                'start'    => $req->query('start'),
                'end'      => $req->query('end'),
                'status'   => $req->query('status'),
                'low_stock'=> $req->query('low_stock'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** GET /api/v1/medications/{id} */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/medications */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                                   => 'sometimes|string',
                'type'                                  => 'sometimes|in:medication',
                'medication_info.name'                  => 'required|string',
                'medication_info.generic_name'          => 'nullable|string',
                'medication_info.strength'              => 'nullable|string',
                'medication_info.dosage_form'           => 'nullable|string',
                'medication_info.manufacturer'          => 'nullable|string',
                'medication_info.barcode'               => 'nullable|string',
                'clinical_info.therapeutic_class'       => 'nullable|string',
                'clinical_info.indications'             => 'nullable|array',
                'clinical_info.contraindications'       => 'nullable|array',
                'clinical_info.side_effects'            => 'nullable|array',
                'clinical_info.drug_interactions'       => 'nullable|array',
                'inventory.current_stock'               => 'nullable|integer',
                'inventory.unit_cost'                   => 'nullable|numeric',
                'inventory.expiry_date'                 => 'nullable|date',
                'inventory.supplier'                    => 'nullable|string',
                'status'                                 => 'nullable|in:active,inactive',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** PUT /api/v1/medications/{id} (cần _rev) */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /** DELETE /api/v1/medications/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/medications/{id}/stock-increase */
    public function stockIncrease(Request $req, string $id)
    {
        $rev   = $req->input('rev');
        $delta = (int) $req->input('delta', 0);
        $res = $this->svc->adjustStock($id, abs($delta), $rev);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/medications/{id}/stock-decrease */
    public function stockDecrease(Request $req, string $id)
    {
        $rev   = $req->input('rev');
        $delta = (int) $req->input('delta', 0);
        $res = $this->svc->adjustStock($id, -abs($delta), $rev);
        return response()->json($res['data'], $res['status']);
    }
}
