<?php

namespace App\Repositories\CouchDB;

use App\Repositories\Interfaces\DoctorsRepositoryInterface;
use App\Services\CouchDB\CouchClient;

class DoctorsRepository extends BaseCouchRepository implements DoctorsRepositoryInterface
{
    protected string $db = 'doctors';

    public function __construct(CouchClient $client) { parent::__construct($client); }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('doctors', 'all', [
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byName(string $q, int $limit = 50, int $skip = 0): array
    {
        $q = mb_strtolower($q);
        return $this->view('doctors', 'by_name', [
            'startkey' => json_encode($q),
            'endkey'   => json_encode($q . "\ufff0"),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byLicense(string $license, int $limit = 50, int $skip = 0): array
    {
        return $this->view('doctors', 'by_license', [
            'key' => json_encode($license),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('doctors', 'by_status', [
            'key' => json_encode($status),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** BỔ SUNG: tìm theo chuyên khoa */
    public function bySpecialty(string $specialty, int $limit = 50, int $skip = 0): array
    {
        // cần view doctors/by_specialty trong _design/doctors
        return $this->view('doctors', 'by_specialty', [
            'key' => json_encode(mb_strtolower($specialty)),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }
}
