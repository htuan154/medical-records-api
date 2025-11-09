<?php

namespace App\Repositories\CouchDB;

use App\Repositories\Interfaces\MedicalRecordsRepositoryInterface;
use App\Services\CouchDB\CouchClient;

class MedicalRecordsRepository extends BaseCouchRepository implements MedicalRecordsRepositoryInterface
{
    protected string $db = 'medical_records';

    public function __construct(CouchClient $client) { parent::__construct($client); }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_records', 'all', [
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byDateRange(string $startIso, string $endIso, int $limit = 50, int $skip = 0): array
    {
        // view medical_records/by_visit_date emit(visit_info.visit_date)
        return $this->view('medical_records', 'by_visit_date', [
            'startkey' => json_encode($startIso),
            'endkey'   => json_encode($endIso),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** BỔ SUNG: theo bệnh nhân */
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_records', 'by_patient', [
            'key' => json_encode($patientId),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** BỔ SUNG: theo bác sĩ */
    public function byDoctor(string $doctorId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_records', 'by_doctor', [
            'key' => json_encode($doctorId),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** BỔ SUNG: theo lịch hẹn */
    public function byAppointment(string $appointmentId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_records', 'by_appointment', [
            'key' => json_encode($appointmentId),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }
     public function byVisitDateRange(string $startIso, string $endIso, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_records', 'by_visit_date', [
            'startkey'     => json_encode($startIso),
            'endkey'       => json_encode($endIso),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    public function byPrimaryICD(string $icdCode, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medical_records', 'by_primary_icd', [
            'key'          => json_encode($icdCode),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
    /** BỔ SUNG: theo trạng thái */
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        // cần view medical_records/by_status
        return $this->view('medical_records', 'by_status', [
            'key' => json_encode($status),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }
}
