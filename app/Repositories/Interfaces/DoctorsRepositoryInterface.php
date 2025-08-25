<?php

namespace App\Repositories\Interfaces;

interface DoctorsRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;

    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byName(string $q, int $limit = 50, int $skip = 0): array;            // doctors/by_name
    public function bySpecialty(string $specialty, int $limit = 50, int $skip = 0): array; // doctors/by_specialty
    public function byLicense(string $license, int $limit = 50, int $skip = 0): array;   // doctors/by_license
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array;     // doctors/by_status
}
