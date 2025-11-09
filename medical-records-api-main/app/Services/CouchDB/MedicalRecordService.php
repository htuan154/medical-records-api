<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\MedicalRecordsRepository;

class MedicalRecordService
{
    public function __construct(
        private CouchClient $client,
        private MedicalRecordsRepository $repo
    ) {}

    /** Đảm bảo _design/medical_records tồn tại với các view cần thiết */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('medical_records');
        $current = $db->get('_design/medical_records');

        $views = [
            'by_patient' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_record' && doc.patient_id) {
    emit(doc.patient_id, null);
  }
}
JS
            ],
            'by_doctor' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_record' && doc.doctor_id) {
    emit(doc.doctor_id, null);
  }
}
JS
            ],
            'by_appointment' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_record' && doc.visit_info && doc.visit_info.appointment_id) {
    emit(doc.visit_info.appointment_id, null);
  }
}
JS
            ],
            'by_visit_date' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_record' && doc.visit_info && doc.visit_info.visit_date) {
    emit(doc.visit_info.visit_date, null);
  }
}
JS
            ],
            'by_status' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_record' && doc.status) {
    emit(doc.status, null);
  }
}
JS
            ],
            'by_primary_icd' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_record' && doc.diagnosis && doc.diagnosis.primary && doc.diagnosis.primary.code) {
    emit(doc.diagnosis.primary.code, null);
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'medical_record') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_id'] = '_design/medical_records';
            $doc['_rev'] = $current['_rev'];
            return $db->putDesign('_design/medical_records', $doc);
        }
        
        $doc['_id'] = '_design/medical_records';
        return $db->putDesign('_design/medical_records', $doc);
    }

    /** List + filter: patient_id | doctor_id | appointment_id | date range | icd | status */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['patient_id'])) {
            return $this->repo->byPatient($filters['patient_id'], $limit, $skip);
        }
        if (!empty($filters['doctor_id'])) {
            return $this->repo->byDoctor($filters['doctor_id'], $limit, $skip);
        }
        if (!empty($filters['appointment_id'])) {
            return $this->repo->byAppointment($filters['appointment_id'], $limit, $skip);
        }
        if (!empty($filters['start']) && !empty($filters['end'])) {
            return $this->repo->byVisitDateRange($filters['start'], $filters['end'], $limit, $skip);
        }
        if (!empty($filters['primary_icd'])) {
            return $this->repo->byPrimaryICD($filters['primary_icd'], $limit, $skip);
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
        $data['type']       = $data['type'] ?? 'medical_record';
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
        $merged['type']       = $current['type'] ?? 'medical_record';
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
                $merged['type']       = $latest['type'] ?? 'medical_record';
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
}
