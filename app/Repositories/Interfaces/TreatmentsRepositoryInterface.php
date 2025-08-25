<?php

namespace App\Repositories\Interfaces;

interface TreatmentsRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;

    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array;     // treatments/by_patient
    public function byDoctor(string $doctorId, int $limit = 50, int $skip = 0): array;       // treatments/by_doctor
    public function byRecord(string $recordId, int $limit = 50, int $skip = 0): array;       // treatments/by_record
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array;         // treatments/by_status
    public function byType(string $type, int $limit = 50, int $skip = 0): array;             // treatments/by_type
    public function byStartRange(string $startIso, string $endIso, int $limit = 100, int $skip = 0): array; // treatments/by_start_date
    public function byMedicationId(string $medId, int $limit = 50, int $skip = 0): array;    // treatments/by_medication_id
}
