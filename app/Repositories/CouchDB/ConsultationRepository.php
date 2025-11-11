<?php

namespace App\Repositories\CouchDB;

use App\Services\CouchDB\CouchClient;
use App\Repositories\Interfaces\ConsultationsRepositoryInterface;

class ConsultationRepository extends BaseCouchRepository implements ConsultationsRepositoryInterface
{
    protected string $db = 'consultations';

    public function __construct(CouchClient $client)
    {
        parent::__construct($client);
    }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('consultations', 'all', [
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
            'descending'   => true,
        ]);
    }

    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('consultations', 'by_patient', [
            'key'          => json_encode($patientId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
            'descending'   => true,
        ]);
    }

    public function byStaff(string $staffId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('consultations', 'by_staff', [
            'key'          => json_encode($staffId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
            'descending'   => true,
        ]);
    }

    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('consultations', 'by_status', [
            'key'          => json_encode($status),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
            'descending'   => true,
        ]);
    }

    public function active(int $limit = 50, int $skip = 0): array
    {
        return $this->view('consultations', 'active', [
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
            'descending'   => true,
        ]);
    }
}
