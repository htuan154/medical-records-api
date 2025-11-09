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

        $doc = ['language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_id'] = '_design/medications';
            $doc['_rev'] = $current['_rev'];
            return $db->putDesign('_design/medications', $doc);
        }
        
        $doc['_id'] = '_design/medications';
        return $db->putDesign('_design/medications', $doc);
    }

    /** List + filters: q (name), barcode, class, expiry range, status, low_stock */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['q'])) {
            return $this->repo->byName($filters['q'], $limit, $skip);
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
        // Lấy document hiện tại để resolve _rev và merge patch
        $current = $this->repo->get($id);
        if (isset($current['error']) && $current['error'] === 'not_found') {
            return ['status' => 404, 'data' => $current];
        }

        $rev = $data['_rev'] ?? ($current['_rev'] ?? null);
        if (!$rev) {
            return [
                'status' => 409,
                'data' => ['error' => 'conflict', 'reason' => 'Missing _rev and cannot resolve latest revision']
            ];
        }

        $merged = $this->mergeDocs($current, $data);
        $merged['_id']        = $id;
        $merged['_rev']       = $rev;
        $merged['type']       = $current['type'] ?? 'medication';
        $merged['updated_at'] = now()->toIso8601String();

        // Thử cập nhật lần 1
        $res = $this->repo->update($id, $merged);
        if (isset($res['error']) && $res['error'] === 'conflict') {
            // Lấy _rev mới nhất và thử lại 1 lần
            $latest = $this->repo->get($id);
            if (!isset($latest['error'])) {
                $merged = $this->mergeDocs($latest, $data);
                $merged['_id']  = $id;
                $merged['_rev'] = $latest['_rev'] ?? null;
                if (!$merged['_rev']) {
                    return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Cannot resolve latest _rev to retry']];
                }
                $merged['type']       = $latest['type'] ?? 'medication';
                $merged['updated_at'] = now()->toIso8601String();
                $res = $this->repo->update($id, $merged);
            }
        }

        return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }

    public function delete(string $id, ?string $rev): array
    {
        // Nếu không có rev -> cố gắng lấy rev hiện tại
        if (!$rev) {
            $doc = $this->repo->get($id);
            if (isset($doc['error'])) {
                return [
                    'status' => $doc['error'] === 'not_found' ? 404 : 400,
                    'data'   => $doc,
                ];
            }
            $rev = $doc['_rev'] ?? null;
            if (!$rev) {
                return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Missing rev and cannot resolve latest revision']];
            }
        }

        $result = $this->repo->delete($id, $rev);

        if (isset($result['error'])) {
            // Xem như idempotent success nếu đã bị xoá trước đó
            if ($result['error'] === 'not_found' && (($result['reason'] ?? '') === 'deleted')) {
                return [
                    'status' => 200,
                    'data' => [ 'ok' => true, 'id' => $id, 'message' => 'Document already deleted' ]
                ];
            }

            // Conflict -> lấy latest rev và retry 1 lần
            if ($result['error'] === 'conflict') {
                $latest = $this->repo->get($id);
                if (!isset($latest['error'])) {
                    $retry = $this->repo->delete($id, $latest['_rev'] ?? '');
                    if (isset($retry['ok']) && $retry['ok']) {
                        return ['status' => 200, 'data' => $retry];
                    }
                    $result = $retry; // rơi xuống return chung
                }
            }
        }

        return ['status' => (!empty($result['ok']) ? 200 : (isset($result['error']) && $result['error'] === 'not_found' ? 404 : 400)), 'data' => $result];
    }

    /** Đệ quy merge (không ghi đè _id) */
    private function mergeDocs(array $base, array $changes): array
    {
        foreach ($changes as $k => $v) {
            if ($k === '_id') continue;
            if (is_array($v) && isset($base[$k]) && is_array($base[$k])) {
                $base[$k] = $this->mergeDocs($base[$k], $v);
            } else {
                $base[$k] = $v;
            }
        }
        return $base;
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
