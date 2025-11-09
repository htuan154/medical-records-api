<?php

namespace App\Repositories\CouchDB;
use App\Repositories\Interfaces\StaffsRepositoryInterface;
use App\Services\CouchDB\CouchClient;

class StaffsRepository extends BaseCouchRepository implements StaffsRepositoryInterface
{
    protected string $db = 'staffs';

    public function __construct(CouchClient $client)
    {
        parent::__construct($client);
    }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('staffs', 'all', [
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byType(string $type, int $limit = 50, int $skip = 0): array
    {
        return $this->view('staffs', 'by_type', [
            'key'          => json_encode($type),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byDepartment(string $dep, int $limit = 50, int $skip = 0): array
    {
        return $this->view('staffs', 'by_department', [
            'key'          => json_encode($dep),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('staffs', 'by_status', [
            'key'          => json_encode($status),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** lọc theo shift.days */
    public function byDay(string $day, int $limit = 50, int $skip = 0): array
    {
        return $this->view('staffs', 'by_day', [
            'key'          => json_encode($day),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
}
