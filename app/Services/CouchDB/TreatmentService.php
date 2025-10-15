<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\TreatmentsRepository;

class TreatmentService
{
    public function __construct(
        private CouchClient $client,
        private TreatmentsRepository $repo
    ) {}

    /** Đảm bảo _design/treatments tồn tại với các view cần thiết */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('treatments');
        $current = $db->get('_design/treatments');

        $views = [
            'by_patient' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'treatment' && doc.patient_id) {
    emit(doc.patient_id, null);
  }
}
JS
            ],
            'by_doctor' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'treatment' && doc.doctor_id) {
    emit(doc.doctor_id, null);
  }
}
JS
            ],
            'by_record' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'treatment' && doc.medical_record_id) {
    emit(doc.medical_record_id, null);
  }
}
JS
            ],
            'by_status' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'treatment' && doc.status) {
    emit(doc.status, null);
  }
}
JS
            ],
            'by_type' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'treatment' && doc.treatment_info && doc.treatment_info.treatment_type) {
    emit(doc.treatment_info.treatment_type, null);
  }
}
JS
            ],
            'by_start_date' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'treatment' && doc.treatment_info && doc.treatment_info.start_date) {
    emit(doc.treatment_info.start_date, null);
  }
}
JS
            ],
            'by_medication_id' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'treatment' && Array.isArray(doc.medications)) {
    for (var i = 0; i < doc.medications.length; i++) {
      var m = doc.medications[i];
      if (m.medication_id) emit(m.medication_id, null);
    }
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'treatment') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_id'] = '_design/treatments';
            $doc['_rev'] = $current['_rev'];
            return $db->putDesign('_design/treatments', $doc);
        }
        
        $doc['_id'] = '_design/treatments';
        return $db->putDesign('_design/treatments', $doc);
    }

    /** List + filter */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['patient_id']))    return $this->repo->byPatient($filters['patient_id'], $limit, $skip);
        if (!empty($filters['doctor_id']))     return $this->repo->byDoctor($filters['doctor_id'], $limit, $skip);
        if (!empty($filters['medical_record_id'])) return $this->repo->byRecord($filters['medical_record_id'], $limit, $skip);
        if (!empty($filters['status']))        return $this->repo->byStatus($filters['status'], $limit, $skip);
        if (!empty($filters['treatment_type']))return $this->repo->byType($filters['treatment_type'], $limit, $skip);
        if (!empty($filters['medication_id'])) return $this->repo->byMedicationId($filters['medication_id'], $limit, $skip);
        if (!empty($filters['start']) && !empty($filters['end']))
            return $this->repo->byStartRange($filters['start'], $filters['end'], $limit, $skip);

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
        $data['type']       = $data['type'] ?? 'treatment';
        $data['created_at'] = $data['created_at'] ?? date('c'); // ISO 8601 format
        $data['updated_at'] = $data['updated_at'] ?? date('c'); // ISO 8601 format
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
    $merged['type']       = $current['type'] ?? 'treatment';
    $merged['updated_at'] = date('c'); // ISO 8601 format

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
        $merged['type']       = $latest['type'] ?? 'treatment';
        $merged['updated_at'] = date('c'); // ISO 8601 format
        $res = $this->repo->update($id, $merged);
      }
    }

    return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }

    public function delete(string $id, ?string $rev): array
    {
    // Nếu không có rev -> cố gắng lấy rev mới nhất
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

    $res = $this->repo->delete($id, $rev);

    // Xử lý idempotent và retry khi conflict
    if (isset($res['error'])) {
      // Nếu đã bị xoá trước đó -> xem như thành công
      if ($res['error'] === 'not_found' && ($res['reason'] ?? '') === 'deleted') {
        return [
          'status' => 200,
          'data' => [
            'ok' => true,
            'id' => $id,
            'message' => 'Document already deleted'
          ]
        ];
      }

      // Nếu conflict -> lấy rev mới nhất và thử lại 1 lần
      if ($res['error'] === 'conflict') {
        $latest = $this->repo->get($id);
        if (!isset($latest['error'])) {
          $retry = $this->repo->delete($id, $latest['_rev'] ?? '');
          if (isset($retry['ok']) && $retry['ok']) {
            return ['status' => 200, 'data' => $retry];
          }
          // Rơi xuống return chung bên dưới
          $res = $retry;
        }
      }
    }

    return ['status' => (!empty($res['ok']) ? 200 : (isset($res['error']) && $res['error'] === 'not_found' ? 404 : 400)), 'data' => $res];
    }

  /** Đệ quy merge mảng (không ghi đè _id) và xử lý dot notation */
  private function mergeDocs(array $base, array $changes): array
  {
    foreach ($changes as $k => $v) {
      if ($k === '_id') continue; // không ghi đè _id
      
      // Xử lý dot notation như "treatment_info.treatment_name"
      if (strpos($k, '.') !== false) {
        $this->setNestedValue($base, $k, $v);
        continue;
      }
      
      if (is_array($v) && isset($base[$k]) && is_array($base[$k])) {
        $base[$k] = $this->mergeDocs($base[$k], $v);
      } else {
        $base[$k] = $v;
      }
    }
    return $base;
  }

  /** Helper để set giá trị theo dot notation */
  private function setNestedValue(array &$array, string $key, $value): void
  {
    $keys = explode('.', $key);
    $current = &$array;
    
    foreach ($keys as $index => $k) {
      if ($index === count($keys) - 1) {
        $current[$k] = $value;
      } else {
        if (!isset($current[$k]) || !is_array($current[$k])) {
          $current[$k] = [];
        }
        $current = &$current[$k];
      }
    }
  }
}
