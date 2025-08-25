<?php

namespace App\Repositories\CouchDB;

use App\Repositories\Interfaces\RolesRepositoryInterface;
use App\Services\CouchDB\CouchClient;

class RolesRepository extends BaseCouchRepository implements RolesRepositoryInterface
{
    protected string $db = 'roles';

    public function __construct(CouchClient $client)
    {
        parent::__construct($client);
    }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('roles', 'all', [
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byName(string $name): array
    {
        return $this->view('roles', 'by_name', [
            'key'          => json_encode(mb_strtolower($name)),
            'include_docs' => true,
            'limit'        => 1,
        ]);
    }

    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('roles', 'by_status', [
            'key'          => json_encode($status),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byPermission(string $perm, int $limit = 50, int $skip = 0): array
    {
        return $this->view('roles', 'by_permission', [
            'key'          => json_encode($perm),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
}
