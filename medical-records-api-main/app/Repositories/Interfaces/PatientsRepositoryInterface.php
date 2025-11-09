<?php

namespace App\Repositories\Interfaces;

interface PatientsRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;     // requires _rev
    public function delete(string $id, string $rev): array;

    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byName(string $q, int $limit = 50, int $skip = 0): array; // view patients/by_name
    public function byPhone(string $phone, int $limit = 50, int $skip = 0): array; // patients/by_phone
    public function byIdNumber(string $idNumber, int $limit = 50, int $skip = 0): array; // patients/by_id_number
    public function byBloodType(string $bloodType, int $limit = 50, int $skip = 0): array; // patients/by_blood_type
    public function byAgeGroup(string $group, int $limit = 50, int $skip = 0): array; // patients/by_age_group
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array; // patients/active_patients
}
