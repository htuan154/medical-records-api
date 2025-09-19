<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\MedicationsRepository;

class MedicationService
{
    public function __construct(
        private CouchClient $client,
        private MedicationsRepository $repo
    ) {}

    /** Đảm bảo _design/medications tồn tại với các view cần thiết */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('medications');
        $current = $db->get('_design/medications');

        $views = [
            'by_name' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medication' && doc.medication_info && doc.medication_info.name) {
    emit(doc.medication_info.name.toLowerCase(), null);
  }
}
JS
            ],
            'by_barcode' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medication' && doc.medication_info && doc.medication_info.barcode) {
    emit(doc.medication_info.barcode, null);
  }
}
JS
            ],
            'by_therapeutic_class' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medication' && doc.clinical_info && doc.clinical_info.therapeutic_class) {
    emit(doc.clinical_info.therapeutic_class, null);
  }
}
JS
            ],
            'by_expiry_date' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medication' && doc.inventory && doc.inventory.expiry_date) {
    emit(doc.inventory.expiry_date, null);
  }
}
JS
            ],
            'by_status' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medication' && doc.status) {
    emit(doc.status, null);
  }
}
JS
            ],
            // Low stock: emit current_stock dưới dạng số để range query
            'low_stock' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medication' && doc.inventory && typeof doc.inventory.current_stock === 'number') {
    emit(doc.inventory.current_stock, null);
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medication') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['_id' => '_design/medications', 'language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/medications', $doc);
        }
        return $db->create($doc);
    }

    /** List + filters: q (name), barcode, class, expiry range, status, low_stock */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['q'])) {
            return $this->repo->ByName($filters['q'], $limit, $skip);
        }
        if (!empty($filters['barcode'])) {
            return $this->repo->byBarcode($filters['barcode']);
        }
        if (!empty($filters['class'])) {
            return $this->repo->byTherapeuticClass($filters['class'], $limit, $skip);
        }
        if (!empty($filters['start']) && !empty($filters['end'])) {
            return $this->repo->byExpiryRange($filters['start'], $filters['end'], $limit, $skip);
        }
        if (!empty($filters['status'])) {
            return $this->repo->byStatus($filters['status'], $limit, $skip);
        }
        if (!empty($filters['low_stock'])) { // ví dụ low_stock=20
            $threshold = (int) $filters['low_stock'];
            return $this->repo->lowStock($threshold, $limit, $skip);
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
        $data['type']       = $data['type'] ?? 'medication';
        $data['created_at'] = $data['created_at'] ?? now()->toIso8601String();
        $data['updated_at'] = $data['updated_at'] ?? now()->toIso8601String();
        return $this->repo->create($data);
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
                'status' => 400,  // ✅ 400 Bad Request, không phải 409
                'data' => [
                    'error' => 'missing_rev',
                    'reason' => 'Revision parameter is required for delete operation'
                ]
            ];
        }

        try {
            $result = $this->repo->delete($id, $rev);
            
            // ✅ Check nếu CouchDB trả về error
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
            
            // ✅ Check nếu operation thành công
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

    /** (tuỳ chọn) cập nhật tồn kho an toàn (tăng/giảm) */
    public function adjustStock(string $id, int $delta, string $rev): array
    {
        $doc = $this->repo->get($id);
        if (isset($doc['error'])) {
            return ['status' => 404, 'data' => $doc];
        }

        $current = (int) ($doc['inventory']['current_stock'] ?? 0);
        $new = max(0, $current + $delta);
        $doc['_rev'] = $rev ?: $doc['_rev'];
        $doc['inventory']['current_stock'] = $new;
        $doc['updated_at'] = now()->toIso8601String();

        $res = $this->repo->update($id, $doc);
        return ['status' => (!empty($res['ok']) ? 200 : 409), 'data' => $res];
    }
}
