<?php

namespace App\Repositories\CouchDB;
use App\Repositories\Interfaces\TreatmentsRepositoryInterface;
use App\Services\CouchDB\CouchClient;

class TreatmentsRepository extends BaseCouchRepository implements TreatmentsRepositoryInterface
{
    protected string $db = 'treatments';

    public function __construct(CouchClient $client)
    {
        parent::__construct($client);
    }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('treatments', 'by_start_date', [
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
            'descending'   => true,
        ]);
    }

    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('treatments', 'by_patient', [
            'key'          => json_encode($patientId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byDoctor(string $doctorId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('treatments', 'by_doctor', [
            'key'          => json_encode($doctorId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byRecord(string $recordId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('treatments', 'by_record', [
            'key'          => json_encode($recordId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('treatments', 'by_status', [
            'key'          => json_encode($status),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byType(string $type, int $limit = 50, int $skip = 0): array
    {
        return $this->view('treatments', 'by_type', [
            'key'          => json_encode($type),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byStartRange(string $startIso, string $endIso, int $limit = 100, int $skip = 0): array
    {
        return $this->view('treatments', 'by_start_date', [
            'startkey'     => json_encode($startIso),
            'endkey'       => json_encode($endIso),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Lọc theo medication_id xuất hiện trong mảng medications */
    public function byMedicationId(string $medId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('treatments', 'by_medication_id', [
            'key'          => json_encode($medId),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
}
