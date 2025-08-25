<?php

namespace App\Repositories\Interfaces;

interface MedicalTestsRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;
    public function byDoctor(string $doctorId, int $limit = 50, int $skip = 0): array;
    public function byTestType(string $type, int $limit = 50, int $skip = 0): array;
    public function byOrderedRange(string $startIso, string $endIso, int $limit = 50, int $skip = 0): array;

    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array; // tests/by_patient
    public function byRecord(string $recordId, int $limit = 50, int $skip = 0): array;   // tests/by_record
    public function byType(string $type, int $limit = 50, int $skip = 0): array;         // tests/by_type
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array;     // tests/by_status
    public function byDateRange(string $startIso, string $endIso, int $limit = 100, int $skip = 0): array; // tests/by_date
}
