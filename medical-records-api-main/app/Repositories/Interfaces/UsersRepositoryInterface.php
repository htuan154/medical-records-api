<?php

namespace App\Repositories\Interfaces;

interface UsersRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array;
    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byUsername(string $username, int $limit = 1, int $skip = 0): array; // users/by_username
    public function byStaff(string $staffId, int $limit = 50, int $skip = 0): array;    // users/by_staff
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array;    // users/by_status
}
