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

    /**
     * Assign staff to consultation (staff nhận chat)
     * @param string $consultationId Consultation ID
     * @param string $staffId Staff ID
     * @param array $staffInfo Staff info
     * @return array Updated consultation
     */
    public function assignStaff(string $consultationId, string $staffId, array $staffInfo): array
    {
        /** @var string $consultationId */
        $consultation = $this->get($consultationId);
        
        // Merge với data cũ để không mất các field khác (patient_info, last_message, etc.)
        $consultation['staff_id'] = $staffId;
        $consultation['staff_info'] = $staffInfo;
        $consultation['status'] = 'active';
        $consultation['updated_at'] = now()->toIso8601String();
        
        return $this->repo->update($consultationId, $consultation);
    }

    /**
     * Close consultation
     * @param string $consultationId Consultation ID
     * @return array Updated consultation
     */
    public function close(string $consultationId): array
    {
        /** @var string $consultationId */
        $consultation = $this->get($consultationId);
        
        // Merge với data cũ
        $consultation['status'] = 'closed';
        $consultation['updated_at'] = now()->toIso8601String();
        
        return $this->repo->update($consultationId, $consultation);
    }

    /**
     * Update unread count
     * @param string $consultationId Consultation ID
     * @param string $userType User type (patient|staff)
     * @param int $count New count
     * @return array Updated consultation
     */
    public function updateUnreadCount(string $consultationId, string $userType, int $count): array
    {
        /** @var string $consultationId */
        /** @var string $userType */
        /** @var int $count */
        $consultation = $this->get($consultationId);
        $field = $userType === 'patient' ? 'unread_count_patient' : 'unread_count_staff';
        
        // Merge với data cũ
        $consultation[$field] = $count;
        $consultation['updated_at'] = now()->toIso8601String();
        
        return $this->repo->update($consultationId, $consultation);
    }

    /**
     * Update last message
     * @param string $consultationId Consultation ID
     * @param string $message Message text
     * @param string $timestamp Timestamp
     * @return array Updated consultation
     */
    public function updateLastMessage(string $consultationId, string $message, string $timestamp): array
    {
        /** @var string $consultationId */
        /** @var string $message */
        /** @var string $timestamp */
        $consultation = $this->get($consultationId);
        
        // Merge với data cũ
        $consultation['last_message'] = $message;
        $consultation['last_message_at'] = $timestamp;
        $consultation['updated_at'] = now()->toIso8601String();
        
        return $this->repo->update($consultationId, $consultation);
    }
}
