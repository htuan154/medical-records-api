<?php

namespace App\Repositories\CouchDB;

use App\Services\CouchDB\CouchClient;
use App\Repositories\Interfaces\AppointmentsRepositoryInterface;

class AppointmentRepository extends BaseCouchRepository implements AppointmentsRepositoryInterface
{
    protected string $db = 'appointments';

    public function __construct(CouchClient $client)
    {
        parent::__construct($client);
    }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('appointments', 'by_date', [
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
            'descending'   => false,
        ]);
    }

    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('appointments', 'by_patient', [
            'key'          => json_encode($patientId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byDoctor(string $doctorId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('appointments', 'by_doctor', [
            'key'          => json_encode($doctorId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Lọc theo khoảng thời gian ISO (UTC) trên scheduled_date */
    public function byDateRange(string $startIso, string $endIso, int $limit = 100, int $skip = 0): array
    {
        return $this->view('appointments', 'by_date', [
            'startkey'     => json_encode($startIso),
            'endkey'       => json_encode($endIso),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('appointments', 'by_status', [
            'key'          => json_encode($status),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
}
