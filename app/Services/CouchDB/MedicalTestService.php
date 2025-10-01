<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\MedicalTestsRepository;

class MedicalTestService
{
    public function __construct(
        private CouchClient $client,
        private MedicalTestsRepository $repo
    ) {}

    /** Đảm bảo _design/medical_tests tồn tại */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('medical_tests');
        $current = $db->get('_design/medical_tests');

        $views = [
            'by_patient' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_test' && doc.patient_id) {
    emit(doc.patient_id, null);
  }
}
JS
            ],
            'by_doctor' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_test' && doc.doctor_id) {
    emit(doc.doctor_id, null);
  }
}
JS
            ],
            'by_record' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_test' && doc.medical_record_id) {
    emit(doc.medical_record_id, null);
  }
}
JS
            ],
            'by_test_type' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_test' && doc.test_info && doc.test_info.test_type) {
    emit(doc.test_info.test_type, null);
  }
}
JS
            ],
            'by_ordered_date' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_test' && doc.test_info && doc.test_info.ordered_date) {
    emit(doc.test_info.ordered_date, null);
  }
}
JS
            ],
            'by_status' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_test' && doc.status) {
    emit(doc.status, null);
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_test') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['_id' => '_design/medical_tests', 'language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/medical_tests', $doc);
        }
        return $db->create($doc);
    }

    /** List + filter: patient_id | doctor_id | record | test_type | ordered range | status */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['patient_id'])) {
            return $this->repo->byPatient($filters['patient_id'], $limit, $skip);
        }
        if (!empty($filters['doctor_id'])) {
            return $this->repo->byDoctor($filters['doctor_id'], $limit, $skip);
        }
        if (!empty($filters['medical_record_id'])) {
            return $this->repo->byRecord($filters['medical_record_id'], $limit, $skip);
        }
        if (!empty($filters['test_type'])) {
            return $this->repo->byTestType($filters['test_type'], $limit, $skip);
        }
        if (!empty($filters['start']) && !empty($filters['end'])) {
            return $this->repo->byOrderedRange($filters['start'], $filters['end'], $limit, $skip);
        }
        if (!empty($filters['status'])) {
            return $this->repo->byStatus($filters['status'], $limit, $skip);
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
        $data['type']       = $data['type'] ?? 'medical_test';
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
        $merged['type']       = $current['type'] ?? 'medical_test';
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
                $merged['type']       = $latest['type'] ?? 'medical_test';
                $merged['updated_at'] = now()->toIso8601String();
                $res = $this->repo->update($id, $merged);
            }
        }

        return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }

    /** Xoá – yêu cầu rev và validate kết quả */
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
            // Nếu đã bị xoá trước đó -> idempotent success
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
}
