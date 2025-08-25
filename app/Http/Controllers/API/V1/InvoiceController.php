<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * GET /api/v1/invoices
     * Params: limit, skip, patient_id, number, start, end, payment_status
     */
    public function index(Request $req)
    {
        try {
            $this->svc->ensureDesignDoc();

            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'patient_id'     => $req->query('patient_id'),
                'number'         => $req->query('number'),
                'start'          => $req->query('start'),
                'end'            => $req->query('end'),
                'payment_status' => $req->query('payment_status'),
            ];

            return response()->json($this->svc->list($limit, $skip, $filters), 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** GET /api/v1/invoices/{id} */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /** POST /api/v1/invoices */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                                   => 'sometimes|string',
                'type'                                  => 'sometimes|in:invoice',
                'patient_id'                            => 'required|string',
                'medical_record_id'                     => 'nullable|string',
                'invoice_info.invoice_number'           => 'required|string',
                'invoice_info.invoice_date'             => 'required|date',
                'invoice_info.due_date'                 => 'nullable|date',
                'services'                              => 'nullable|array',
                'services.*.service_type'               => 'nullable|string',
                'services.*.description'                => 'nullable|string',
                'services.*.quantity'                   => 'nullable|numeric',
                'services.*.unit_price'                 => 'nullable|numeric',
                'services.*.total_price'                => 'nullable|numeric',
                'payment_info.subtotal'                 => 'nullable|numeric',
                'payment_info.tax_rate'                 => 'nullable|numeric',
                'payment_info.tax_amount'               => 'nullable|numeric',
                'payment_info.total_amount'             => 'nullable|numeric',
                'payment_info.insurance_coverage'       => 'nullable|numeric',
                'payment_info.insurance_amount'         => 'nullable|numeric',
                'payment_info.patient_payment'          => 'nullable|numeric',
                'payment_status'                         => 'nullable|string',
                'payment_method'                         => 'nullable|string',
                'paid_date'                              => 'nullable|date',
            ]);

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /** PUT /api/v1/invoices/{id} (cần _rev) */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /** DELETE /api/v1/invoices/{id}?rev=xxx */
    public function destroy(Request $req, string $id)
    {
        $rev = $req->query('rev') ?? $req->input('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }
}
