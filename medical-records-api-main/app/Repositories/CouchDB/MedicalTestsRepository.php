<?php

namespace App\Repositories\CouchDB;

use App\Repositories\Interfaces\MedicalTestsRepositoryInterface;
use App\Services\CouchDB\CouchClient;

class MedicalTestsRepository extends BaseCouchRepository implements MedicalTestsRepositoryInterface
{
    protected string $db = 'medical_tests';

    public function __construct(CouchClient $client) { parent::__construct($client); }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_tests', 'all', [
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byType(string $type, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_tests', 'by_test_type', [
            'key' => json_encode($type),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byDateRange(string $startIso, string $endIso, int $limit = 50, int $skip = 0): array
    {
        // view medical_tests/by_ordered_date emit(test_info.ordered_date)
        return $this->view('medical_tests', 'by_ordered_date', [
            'startkey' => json_encode($startIso),
            'endkey'   => json_encode($endIso),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** BỔ SUNG: theo bệnh nhân */
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_tests', 'by_patient', [
            'key' => json_encode($patientId),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** BỔ SUNG: theo hồ sơ khám */
    public function byRecord(string $recordId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_tests', 'by_record', [
            'key' => json_encode($recordId),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }
public function byDoctor(string $doctorId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_tests', 'by_doctor', [
            'key' => json_encode($doctorId),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** 🔥 mới */
    public function byTestType(string $type, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_tests', 'by_test_type', [
            'key' => json_encode($type),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byOrderedRange(string $startIso, string $endIso, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_tests', 'by_ordered_date', [
            'startkey' => json_encode($startIso),
            'endkey'   => json_encode($endIso),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_tests', 'by_status', [
            'key' => json_encode($status),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }
}
