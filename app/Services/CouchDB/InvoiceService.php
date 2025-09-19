<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\InvoicesRepository;
use App\Repositories\CouchDB\MedicationsRepository;

class InvoiceService
{
    public function __construct(
        private CouchClient $client,
        private InvoicesRepository $repo,
        private MedicationsRepository $medicationsRepo  // ✅ Inject MedicationsRepository
    ) {}

    /** Đảm bảo _design/invoices tồn tại với các view cần thiết */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('invoices');
        $current = $db->get('_design/invoices');

        $views = [
            'by_number' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'invoice' && doc.invoice_info && doc.invoice_info.invoice_number) {
    emit(doc.invoice_info.invoice_number, null);
  }
}
JS
            ],
            'by_patient' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'invoice' && doc.patient_id) {
    emit(doc.patient_id, null);
  }
}
JS
            ],
            'by_invoice_date' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'invoice' && doc.invoice_info && doc.invoice_info.invoice_date) {
    emit(doc.invoice_info.invoice_date, null);
  }
}
JS
            ],
            'by_due_date' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'invoice' && doc.invoice_info && doc.invoice_info.due_date) {
    emit(doc.invoice_info.due_date, null);
  }
}
JS
            ],
            'by_payment_status' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'invoice' && doc.payment_status) {
    emit(doc.payment_status, null);
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'invoice') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['_id' => '_design/invoices', 'language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/invoices', $doc);
        }
        return $db->create($doc);
    }

    /** List + filter: patient_id | number | date range | payment_status */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['patient_id'])) {
            return $this->repo->byPatient($filters['patient_id'], $limit, $skip);
        }
        if (!empty($filters['number'])) {
            return $this->repo->byNumber($filters['number']);
        }
        if (!empty($filters['start']) && !empty($filters['end'])) {
            return $this->repo->byDateRange($filters['start'], $filters['end'], $limit, $skip);
        }
        if (!empty($filters['payment_status'])) {
            return $this->repo->byPaymentStatus($filters['payment_status'], $limit, $skip);
        }
        return $this->repo->allFull($limit, $skip);
    }

    public function find(string $id): array
    {
        $doc = $this->repo->get($id);
        if (isset($doc['error']) && $doc['error'] === 'not_found') {
            return ['status' => 404, 'data' => $doc];
        }
        return ['status' => 200, 'data' => $doc];
    }

    public function create(array $data): array
    {
        $data['type']       = $data['type'] ?? 'invoice';
        $data['created_at'] = $data['created_at'] ?? now()->toIso8601String();
        $data['updated_at'] = $data['updated_at'] ?? now()->toIso8601String();
        
        try {
            // ✅ BƯỚC 1: Tạo invoice
            $invoiceResult = $this->repo->create($data);
            
            if (!isset($invoiceResult['ok']) || !$invoiceResult['ok']) {
                return $invoiceResult;
            }
            
            // ✅ BƯỚC 2: Decrease medication stock nếu có
            $this->decreaseMedicationStock($data['services'] ?? []);
            
            return $invoiceResult;
            
        } catch (\Exception $e) {
            return [
                'error' => 'create_failed',
                'message' => $e->getMessage()
            ];
        }
    }

    // ✅ NEW: Decrease medication stock
    private function decreaseMedicationStock(array $services): void
    {
        foreach ($services as $service) {
            if (
                ($service['service_type'] ?? '') === 'medication' && 
                !empty($service['medication_id']) && 
                ($service['quantity'] ?? 0) > 0
            ) {
                try {
                    $medicationId = $service['medication_id'];
                    $quantity = (int) $service['quantity'];
                    
                    // Get current medication doc
                    $medicationDoc = $this->medicationsRepo->get($medicationId);
                    
                    if (!isset($medicationDoc['error'])) {
                        $currentStock = (int) ($medicationDoc['inventory']['current_stock'] ?? 0);
                        $newStock = max(0, $currentStock - $quantity);
                        
                        // Update stock
                        $medicationDoc['inventory']['current_stock'] = $newStock;
                        $medicationDoc['updated_at'] = now()->toIso8601String();
                        
                        $this->medicationsRepo->update($medicationId, $medicationDoc);
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail invoice creation
                    error_log("Failed to decrease medication stock for {$medicationId}: " . $e->getMessage());
                }
            }
        }
    }

    public function update(string $id, array $data): array
    {
        $data['_id'] = $id;
        if (empty($data['_rev'])) {
            return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Missing _rev']];
        }
        $data['updated_at'] = now()->toIso8601String();
        $res = $this->repo->update($id, $data);
        return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }

    public function delete(string $id, ?string $rev): array
    {
        if (!$rev) {
            return [
                'status' => 400,
                'data' => [
                    'error' => 'missing_rev',
                    'reason' => 'Revision parameter is required for delete operation'
                ]
            ];
        }

        try {
            $result = $this->repo->delete($id, $rev);
            
            if (isset($result['error'])) {
                $status = match($result['error']) {
                    'not_found' => 404,
                    'conflict' => 409,
                    default => 400
                };
                
                return [
                    'status' => $status,
                    'data' => $result
                ];
            }
            
            if (isset($result['ok']) && $result['ok'] === true) {
                return [
                    'status' => 200,
                    'data' => $result
                ];
            }
            
            return [
                'status' => 400,
                'data' => [
                    'error' => 'unknown_result',
                    'reason' => 'Delete operation did not return expected result',
                    'result' => $result
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'data' => [
                    'error' => 'delete_failed',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }
}
