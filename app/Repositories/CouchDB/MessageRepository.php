<?php

namespace App\Repositories\CouchDB;

use App\Services\CouchDB\CouchClient;
use App\Repositories\Interfaces\MessagesRepositoryInterface;

class MessageRepository extends BaseCouchRepository implements MessagesRepositoryInterface
{
    protected string $db = 'consultations';

    public function __construct(CouchClient $client)
    {
        parent::__construct($client);
    }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('messages', 'all', [
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
            'descending'   => true,
        ]);
    }

    public function byConsultation(string $consultationId, int $limit = 100, int $skip = 0): array
    {
        return $this->view('messages', 'by_consultation', [
            'startkey'     => json_encode([$consultationId, null]),
            'endkey'       => json_encode([$consultationId, []]),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function bySender(string $senderId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('messages', 'by_sender', [
            'key'          => json_encode($senderId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
            'descending'   => true,
        ]);
    }
}
