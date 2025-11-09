<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\AppointmentRepository;
use Illuminate\Support\Facades\Log;

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

        $doc = ['language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_id'] = '_design/appointments';
            $doc['_rev'] = $current['_rev'];
            return $db->putDesign('_design/appointments', $doc);
        }
        
        $doc['_id'] = '_design/appointments';
        return $db->putDesign('_design/appointments', $doc);
    }

    /** List + các filter cơ bản */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        try {
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
        } catch (\Throwable $e) {
            Log::error('AppointmentService::list failed', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'limit' => $limit,
                'skip' => $skip,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
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

    /** Xoá – yêu cầu rev và validate kết quả */
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
            
            // ✅ Trường hợp không xác định được kết quả
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
