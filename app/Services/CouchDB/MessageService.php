<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\MessageRepository;
use Illuminate\Support\Facades\Log;

class MessageService
{
    public function __construct(
        private CouchClient $client,
        private MessageRepository $repo
    ) {}

    /** Tạo/cập nhật _design/messages với các view cần thiết */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('consultations');
        $current = $db->get('_design/messages');

        $views = [
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'message') {
    emit(doc._id, null);
  }
}
JS
            ],
            'by_consultation' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'message' && doc.consultation_id) {
    emit([doc.consultation_id, doc.created_at], null);
  }
}
JS
            ],
            'by_sender' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'message' && doc.sender_id) {
    emit(doc.sender_id, null);
  }
}
JS
            ],
        ];

        $doc = ['language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_id'] = '_design/messages';
            $doc['_rev'] = $current['_rev'];
            return $db->putDesign('_design/messages', $doc);
        }
        
        $doc['_id'] = '_design/messages';
        return $db->putDesign('_design/messages', $doc);
    }

    /** List messages with filters */
    public function list(int $limit = 100, int $skip = 0, array $filters = []): array
    {
        try {
            if (!empty($filters['consultation_id'])) {
                return $this->repo->byConsultation($filters['consultation_id'], $limit, $skip);
            }
            if (!empty($filters['sender_id'])) {
                return $this->repo->bySender($filters['sender_id'], $limit, $skip);
            }
            return $this->repo->allFull($limit, $skip);
        } catch (\Throwable $e) {
            Log::error('MessageService::list failed', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);
            throw $e;
        }
    }

    /** Get single message */
    public function get(string $id): array
    {
        return $this->repo->get($id);
    }

    /** Create new message */
    public function create(array $data): array
    {
        $data['type'] = 'message';
        $data['created_at'] = $data['created_at'] ?? now()->toIso8601String();
        $data['is_read'] = $data['is_read'] ?? false;

        return $this->repo->create($data);
    }

    /**
     * Update message (mainly for marking as read)
     * @param string $id Message ID
     * @param array $data Data to update
     * @return array Updated message
     */
    public function update(string $id, array $data): array
    {
        // Merge với data cũ để không mất field
        $message = $this->get($id);
        $merged = array_merge($message, $data);
        
        return $this->repo->update($id, $merged);
    }

    /**
     * Delete message
     * @param string $id Message ID
     * @param string $rev Revision
     * @return array Delete result
     */
    public function delete(string $id, string $rev): array
    {
        /** @var string $id @var string $rev */
        return $this->repo->delete($id, $rev);
    }

    /**
     * Mark message as read
     * @param string $messageId Message ID
     * @return array Updated message
     */
    public function markAsRead(string $messageId): array
    {
        /** @var string $messageId */
        $message = $this->get($messageId);
        
        // Merge với data cũ
        $message['is_read'] = true;
        
        return $this->repo->update($messageId, $message);
    }

    /**
     * Mark multiple messages as read
     * @param array $messageIds Array of message IDs
     * @return array Results
     */
    public function markMultipleAsRead(array $messageIds): array
    {
        /** @var array $messageIds */
        $results = [];
        foreach ($messageIds as $id) {
            try {
                $results[$id] = $this->markAsRead($id);
            } catch (\Throwable $e) {
                Log::error("Failed to mark message {$id} as read", ['error' => $e->getMessage()]);
                $results[$id] = ['error' => $e->getMessage()];
            }
        }
        return $results;
    }

    /**
     * Get messages by consultation
     * @param string $consultationId Consultation ID
     * @param int $limit Limit
     * @param int $skip Skip
     * @return array Messages
     */
    public function getByConsultation(string $consultationId, int $limit = 100, int $skip = 0): array
    {
        /** @var string $consultationId */
        /** @var int $limit */
        /** @var int $skip */
        return $this->repo->byConsultation($consultationId, $limit, $skip);
    }
}
