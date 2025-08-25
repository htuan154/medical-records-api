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

        $doc = ['_id' => '_design/treatments', 'language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/treatments', $doc);
        }
        return $db->create($doc);
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
            return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Missing rev']];
        }
        $res = $this->repo->delete($id, $rev);
        return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }
}
