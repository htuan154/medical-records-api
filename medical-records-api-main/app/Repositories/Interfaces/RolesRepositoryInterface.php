<?php

namespace App\Repositories\Interfaces;

interface RolesRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;

    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byName(string $name): array;                                   // roles/by_name
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array; // roles/by_status
    public function byPermission(string $perm, int $limit = 50, int $skip = 0): array; // roles/by_permission
}
