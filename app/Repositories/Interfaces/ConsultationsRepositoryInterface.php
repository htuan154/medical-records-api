<?php

namespace App\Repositories\Interfaces;

interface ConsultationsRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;

    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array;
    public function byStaff(string $staffId, int $limit = 50, int $skip = 0): array;
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array;
    public function active(int $limit = 50, int $skip = 0): array;
}
