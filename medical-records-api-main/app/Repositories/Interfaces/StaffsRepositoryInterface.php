<?php

namespace App\Repositories\Interfaces;

interface StaffsRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;

    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byType(string $type, int $limit = 50, int $skip = 0): array;        // staffs/by_type
    public function byDepartment(string $dep, int $limit = 50, int $skip = 0): array;   // staffs/by_department
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array;    // staffs/by_status
    public function byDay(string $day, int $limit = 50, int $skip = 0): array;          // staffs/by_day (shift.days)
}
