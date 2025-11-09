<?php

namespace App\Repositories\CouchDB;

use App\Repositories\Interfaces\UsersRepositoryInterface;

use App\Services\CouchDB\CouchClient;

class UsersRepository extends BaseCouchRepository implements UsersRepositoryInterface
{
    protected string $db = 'users';

    public function __construct(CouchClient $client)
    {
        parent::__construct($client);
    }

    /** Lấy danh sách đầy đủ (kèm doc) */
    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('users', 'all', [
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Tìm theo username */
    public function byUsername(string $username, int $limit = 1, int $skip = 0): array
    {
        return $this->view('users', 'by_username', [
            'key'          => json_encode(mb_strtolower($username)),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Tìm theo staff id liên kết */
    public function byStaff(string $staffId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('users', 'by_staff', [
            'key'          => json_encode($staffId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('users', 'by_patient', [
            'key'          => json_encode($patientId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('users', 'by_status', [
            'key'          => json_encode($status),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
}
