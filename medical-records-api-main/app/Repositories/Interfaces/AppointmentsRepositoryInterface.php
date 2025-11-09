<?php

namespace App\Repositories\Interfaces;

interface AppointmentsRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;

    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array; // appointments/by_patient
    public function byDoctor(string $doctorId, int $limit = 50, int $skip = 0): array;   // appointments/by_doctor
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array;     // appointments/by_status
    public function byDateRange(string $startIso, string $endIso, int $limit = 100, int $skip = 0): array; // appointments/by_date
}
