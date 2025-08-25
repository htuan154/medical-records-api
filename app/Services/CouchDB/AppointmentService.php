<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\AppointmentRepository;

class AppointmentService
{
    public function __construct(
        private CouchClient $client,
        private AppointmentRepository $repo
    ) {}

    /** Tạo/cập nhật _design/appointments với các view cần thiết */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('appointments');
        $current = $db->get('_design/appointments');

        $views = [
            'by_patient' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'appointment' && doc.patient_id) {
    emit(doc.patient_id, null);
  }
}
JS
            ],
            'by_doctor' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'appointment' && doc.doctor_id) {
    emit(doc.doctor_id, null);
  }
}
JS
            ],
            'by_date' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'appointment' && doc.appointment_info && doc.appointment_info.scheduled_date) {
    emit(doc.appointment_info.scheduled_date, null);
  }
}
JS
            ],
            'by_status' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'appointment' && doc.status) {
    emit(doc.status, null);
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'appointment') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['_id' => '_design/appointments', 'language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/appointments', $doc);
        }
        return $db->create($doc);
    }

    /** List + các filter cơ bản */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['patient_id'])) {
            return $this->repo->byPatient($filters['patient_id'], $limit, $skip);
        }
        if (!empty($filters['doctor_id'])) {
            return $this->repo->byDoctor($filters['doctor_id'], $limit, $skip);
        }
        if (!empty($filters['start']) && !empty($filters['end'])) {
            return $this->repo->byDateRange($filters['start'], $filters['end'], $limit, $skip);
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
        $data['type']       = $data['type'] ?? 'appointment';
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
