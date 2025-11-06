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

    /** Update message (mainly for marking as read) */
    public function update(string $id, array $data): array
    {
        return $this->repo->update($id, $data);
    }

    /** Delete message */
    public function delete(string $id, string $rev): array
    {
        return $this->repo->delete($id, $rev);
    }

    /** Mark message as read */
    public function markAsRead(string $messageId): array
    {
        $message = $this->get($messageId);
        
        return $this->update($messageId, [
            '_rev' => $message['_rev'],
            'is_read' => true,
        ]);
    }

    /** Mark multiple messages as read */
    public function markMultipleAsRead(array $messageIds): array
    {
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

    /** Get messages by consultation */
    public function getByConsultation(string $consultationId, int $limit = 100, int $skip = 0): array
    {
        return $this->repo->byConsultation($consultationId, $limit, $skip);
    }
}
