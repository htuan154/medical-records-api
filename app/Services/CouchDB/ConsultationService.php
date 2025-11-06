<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\ConsultationRepository;
use Illuminate\Support\Facades\Log;

class ConsultationService
{
    public function __construct(
        private CouchClient $client,
        private ConsultationRepository $repo
    ) {}

    /** Tạo/cập nhật _design/consultations với các view cần thiết */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('consultations');
        $current = $db->get('_design/consultations');

        $views = [
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'consultation') {
    emit(doc._id, null);
  }
}
JS
            ],
            'by_patient' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'consultation' && doc.patient_id) {
    emit(doc.patient_id, null);
  }
}
JS
            ],
            'by_staff' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'consultation' && doc.staff_id) {
    emit(doc.staff_id, null);
  }
}
JS
            ],
            'by_status' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'consultation' && doc.status) {
    emit(doc.status, null);
  }
}
JS
            ],
            'active' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'consultation' && doc.status === 'active') {
    emit(doc.updated_at, null);
  }
}
JS
            ],
        ];

        $doc = ['language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_id'] = '_design/consultations';
            $doc['_rev'] = $current['_rev'];
            return $db->putDesign('_design/consultations', $doc);
        }
        
        $doc['_id'] = '_design/consultations';
        return $db->putDesign('_design/consultations', $doc);
    }

    /** List consultations with filters */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        try {
            if (!empty($filters['patient_id'])) {
                return $this->repo->byPatient($filters['patient_id'], $limit, $skip);
            }
            if (!empty($filters['staff_id'])) {
                return $this->repo->byStaff($filters['staff_id'], $limit, $skip);
            }
            if (!empty($filters['status'])) {
                return $this->repo->byStatus($filters['status'], $limit, $skip);
            }
            if (!empty($filters['active'])) {
                return $this->repo->active($limit, $skip);
            }
            return $this->repo->allFull($limit, $skip);
        } catch (\Throwable $e) {
            Log::error('ConsultationService::list failed', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);
            throw $e;
        }
    }

    /** Get single consultation */
    public function get(string $id): array
    {
        return $this->repo->get($id);
    }

    /** Create new consultation */
    public function create(array $data): array
    {
        $data['type'] = 'consultation';
        $data['created_at'] = $data['created_at'] ?? now()->toIso8601String();
        $data['updated_at'] = $data['updated_at'] ?? now()->toIso8601String();
        $data['status'] = $data['status'] ?? 'waiting';
        $data['unread_count_patient'] = $data['unread_count_patient'] ?? 0;
        $data['unread_count_staff'] = $data['unread_count_staff'] ?? 0;

        return $this->repo->create($data);
    }

    /** Update consultation */
    public function update(string $id, array $data): array
    {
        $data['updated_at'] = now()->toIso8601String();
        return $this->repo->update($id, $data);
    }

    /** Delete consultation */
    public function delete(string $id, string $rev): array
    {
        return $this->repo->delete($id, $rev);
    }

    /** Assign staff to consultation (staff nhận chat) */
    public function assignStaff(string $consultationId, string $staffId, array $staffInfo): array
    {
        $consultation = $this->get($consultationId);
        
        return $this->update($consultationId, [
            '_rev' => $consultation['_rev'],
            'staff_id' => $staffId,
            'staff_info' => $staffInfo,
            'status' => 'active',
        ]);
    }

    /** Close consultation */
    public function close(string $consultationId): array
    {
        $consultation = $this->get($consultationId);
        
        return $this->update($consultationId, [
            '_rev' => $consultation['_rev'],
            'status' => 'closed',
        ]);
    }

    /** Update unread count */
    public function updateUnreadCount(string $consultationId, string $userType, int $count): array
    {
        $consultation = $this->get($consultationId);
        $field = $userType === 'patient' ? 'unread_count_patient' : 'unread_count_staff';
        
        return $this->update($consultationId, [
            '_rev' => $consultation['_rev'],
            $field => $count,
        ]);
    }

    /** Update last message */
    public function updateLastMessage(string $consultationId, string $message, string $timestamp): array
    {
        $consultation = $this->get($consultationId);
        
        return $this->update($consultationId, [
            '_rev' => $consultation['_rev'],
            'last_message' => $message,
            'last_message_at' => $timestamp,
        ]);
    }
}
