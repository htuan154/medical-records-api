<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\InvoicesRepository;
use App\Repositories\CouchDB\MedicationsRepository;

class InvoiceService
{
    public function __construct(
        private CouchClient $client,
        private InvoicesRepository $repo,
        private MedicationsRepository $medicationsRepo
    ) {}

    // Ensure design doc exists
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
            'by_medical_record' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'invoice' && doc.medical_record_id) {
    emit(doc.medical_record_id, null);
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

        $doc = ['language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_id'] = '_design/invoices';
            $doc['_rev'] = $current['_rev'];
            return $db->putDesign('_design/invoices', $doc);
        }
        
        $doc['_id'] = '_design/invoices';
        return $db->putDesign('_design/invoices', $doc);
    }

    // List with filters
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['patient_id'])) {
            return $this->repo->byPatient($filters['patient_id'], $limit, $skip);
        }
        if (!empty($filters['medical_record_id'])) {
            return $this->repo->byMedicalRecord($filters['medical_record_id'], $limit, $skip);
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
        // Log the entire invoice payload for debugging
        error_log("🧾 InvoiceService::create called");
        error_log("📋 Invoice data: " . json_encode($data, JSON_PRETTY_PRINT));
        
        // Ignore _rev on create to avoid conflicts from Swagger examples
        unset($data['_rev']);

        $data['type']       = $data['type'] ?? 'invoice';
        $data['created_at'] = $data['created_at'] ?? now()->toIso8601String();
        $data['updated_at'] = $data['updated_at'] ?? now()->toIso8601String();
        $data['services']   = $this->ensureMedicationLinked($data['services'] ?? []);

        try {
            $invoiceResult = $this->repo->create($data);
            if (!isset($invoiceResult['ok']) || !$invoiceResult['ok']) {
                return $invoiceResult;
            }

            // Optional: adjust medication stock based on services
            $this->decreaseMedicationStock($data['services'] ?? []);

            return $invoiceResult;
        } catch (\Exception $e) {
            return [
                'error' => 'create_failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    // Decrease medication stock if service contains medication items
    private function decreaseMedicationStock(array $services): void
    {
        error_log("🔍 decreaseMedicationStock called with " . count($services) . " services");
        
        foreach ($services as $index => $service) {
            $serviceType = $service['service_type'] ?? '';
            $medicationId = $service['medication_id'] ?? null;
            $quantity = $service['quantity'] ?? 0;
            
            error_log("📦 Service #{$index}: type={$serviceType}, med_id={$medicationId}, qty={$quantity}");
            
            if ($serviceType === 'medication' && !empty($medicationId) && $quantity > 0) {
                try {
                    error_log("💊 Decreasing stock for medication ID: {$medicationId}, quantity: {$quantity}");
                    
                    $medicationDoc = $this->medicationsRepo->get($medicationId);
                    if (!isset($medicationDoc['error'])) {
                        $currentStock = (int) ($medicationDoc['inventory']['current_stock'] ?? 0);
                        $newStock = max(0, $currentStock - $quantity);
                        
                        error_log("📊 Stock update: {$currentStock} -> {$newStock} (decreased by {$quantity})");
                        
                        $medicationDoc['inventory']['current_stock'] = $newStock;
                        $medicationDoc['updated_at'] = now()->toIso8601String();
                        $updateResult = $this->medicationsRepo->update($medicationId, $medicationDoc);
                        
                        error_log("✅ Stock decreased successfully: " . json_encode($updateResult));
                    } else {
                        error_log("❌ Medication not found: {$medicationId}");
                    }
                } catch (\Exception $e) {
                    error_log("❌ Failed to decrease medication stock for {$medicationId}: " . $e->getMessage());
                }
            } else {
                if ($serviceType === 'medication') {
                    error_log("⚠️ Skipping medication service: missing med_id or invalid quantity");
                }
            }
        }
    }

    public function update(string $id, array $data): array
    {
        // Fetch current doc and merge; retry once on conflict
        $current = $this->repo->get($id);
        if (isset($current['error']) && $current['error'] === 'not_found') {
            return ['status' => 404, 'data' => $current];
        }

        $oldMedicationServices = $current['services'] ?? [];
        $data['services'] = $this->ensureMedicationLinked($data['services'] ?? []);

        $attempt = function(array $baseDoc) use ($id, $data) {
            $merged = $this->mergeDocs($baseDoc, $data);
            $merged['_id'] = $id;
            $merged['_rev'] = $baseDoc['_rev'] ?? ($data['_rev'] ?? null);
            $merged['type'] = $baseDoc['type'] ?? 'invoice';
            $merged['updated_at'] = now()->toIso8601String();
            $result = $this->repo->update($id, $merged);
            return [$result, $merged];
        };

        [$res, $mergedDoc] = $attempt($current);
        if (($res['error'] ?? '') === 'conflict') {
            $latest = $this->repo->get($id);
            if (!isset($latest['error'])) {
                [$res2, $mergedDoc] = $attempt($latest);
                if (!isset($res2['error'])) {
                    $this->adjustMedicationStockDelta($oldMedicationServices, $mergedDoc['services'] ?? []);
                }
                return ['status' => (!isset($res2['error']) ? 200 : 409), 'data' => $res2];
            }
        }

        if (!isset($res['error'])) {
            $this->adjustMedicationStockDelta($oldMedicationServices, $mergedDoc['services'] ?? []);
        }

        return ['status' => (!isset($res['error']) ? 200 : 400), 'data' => $res];
    }

    /**
     * Đảm bảo service thuốc có medication_id; nếu thiếu thì thử tìm theo mô tả/name và gán giá tồn kho.
     */
    private function ensureMedicationLinked(array $services): array
    {
        foreach ($services as &$service) {
            $serviceType = $service['service_type'] ?? '';
            if ($serviceType !== 'medication') {
                continue;
            }

            if (empty($service['medication_id'])) {
                $resolved = $this->resolveMedicationFromDescription($service['description'] ?? '');
                if ($resolved) {
                    $service['medication_id'] = $resolved['_id'] ?? $resolved['id'] ?? null;
                    $service['available_stock'] = (int) ($resolved['inventory']['current_stock'] ?? 0);
                    if (empty($service['unit_price'])) {
                        $service['unit_price'] = (int) ($resolved['inventory']['unit_cost'] ?? 0);
                    }
                }
            }
        }
        unset($service);

        return $services;
    }

    /**
     * Tính delta tồn kho và cập nhật vào medications.
     * Delta = newQty - oldQty (theo medication_id, cộng dồn nếu nhiều dòng).
     */
    private function adjustMedicationStockDelta(array $old, array $new): void
    {
        $newMap = $this->collectMedicationQuantities($new);
        $oldMap = $this->collectMedicationQuantities($old);

        foreach ($newMap as $medId => $newQty) {
            $oldQty = $oldMap[$medId] ?? 0;
            $delta = $newQty - $oldQty;
            if ($delta === 0) {
                continue;
            }

            error_log("🔄 Adjusting stock for {$medId} with delta {$delta}");
            try {
                $medicationDoc = $this->medicationsRepo->get($medId);
                if (isset($medicationDoc['error'])) {
                    error_log("❌ Medication not found for delta update: {$medId}");
                    continue;
                }

                $currentStock = (int) ($medicationDoc['inventory']['current_stock'] ?? 0);
                $newStock = max(0, $currentStock - $delta); // delta>0: reduce; delta<0: tăng lại

                $medicationDoc['inventory']['current_stock'] = $newStock;
                $medicationDoc['updated_at'] = now()->toIso8601String();
                $this->medicationsRepo->update($medId, $medicationDoc);

                error_log("✅ Stock adjusted {$currentStock} -> {$newStock} for {$medId}");
            } catch (\Exception $e) {
                error_log("❌ Failed to adjust stock for {$medId}: " . $e->getMessage());
            }
        }
    }

    /**
     * Trả về map medication_id => tổng quantity từ danh sách service
     */
    private function collectMedicationQuantities(array $services): array
    {
        $map = [];
        foreach ($services as $s) {
            if (($s['service_type'] ?? '') !== 'medication') {
                continue;
            }
            $medId = $s['medication_id'] ?? null;
            if (!$medId) {
                continue;
            }
            $qty = (int) ($s['quantity'] ?? 0);
            $map[$medId] = ($map[$medId] ?? 0) + $qty;
        }
        return $map;
    }

    /**
     * Thử tìm thuốc theo mô tả (lấy phần trước dấu '-' để match tên)
     */
    private function resolveMedicationFromDescription(string $description): ?array
    {
        $parts = explode('-', $description);
        $name = trim($parts[0] ?? '');
        if ($name === '') {
            return null;
        }

        try {
            $res = $this->medicationsRepo->byName(mb_strtolower($name), 1, 0);
            if (is_array($res) && isset($res['rows'][0]['doc'])) {
                return $res['rows'][0]['doc'];
            }
        } catch (\Exception $e) {
            error_log("❌ resolveMedicationFromDescription failed for '{$description}': " . $e->getMessage());
        }

        return null;
    }

    public function delete(string $id, ?string $rev): array
    {
        try {
            if (!$rev) {
                $doc = $this->repo->get($id);
                if (isset($doc['error'])) {
                    // already deleted or missing
                    return ['status' => 200, 'data' => ['ok' => true, 'id' => $id, 'already' => 'deleted_or_missing']];
                }
                $rev = $doc['_rev'] ?? null;
            }

            if (!$rev) {
                return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Missing rev']];
            }

            $result = $this->repo->delete($id, $rev);
            if (($result['error'] ?? '') === 'conflict') {
                $latest = $this->repo->get($id);
                if (!isset($latest['error']) && isset($latest['_rev'])) {
                    $retry = $this->repo->delete($id, $latest['_rev']);
                    if (!isset($retry['error'])) {
                        return ['status' => 200, 'data' => $retry];
                    }
                    if (($retry['error'] ?? '') === 'not_found') {
                        return ['status' => 200, 'data' => ['ok' => true, 'id' => $id, 'already' => 'deleted']];
                    }
                    return ['status' => 409, 'data' => $retry];
                }
            }

            if (($result['error'] ?? '') === 'not_found') {
                return ['status' => 200, 'data' => ['ok' => true, 'id' => $id, 'already' => 'deleted']];
            }

            return ['status' => (!isset($result['error']) ? 200 : 400), 'data' => $result];
        } catch (\Exception $e) {
            return ['status' => 500, 'data' => ['error' => 'server_error', 'message' => $e->getMessage()]];
        }
    }

    // recursive merge helper; associative arrays merge, numeric arrays replace
    private function mergeDocs(array $base, array $incoming): array
    {
        $result = $base;
        foreach ($incoming as $k => $v) {
            if ($k === '_id' || $k === '_rev') continue;
            if (is_array($v) && isset($base[$k]) && is_array($base[$k])) {
                $isAssoc = static function(array $arr) { return array_keys($arr) !== range(0, count($arr) - 1); };
                if ($isAssoc($v) && $isAssoc($base[$k])) {
                    $result[$k] = $this->mergeDocs($base[$k], $v);
                } else {
                    $result[$k] = $v;
                }
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }
}
