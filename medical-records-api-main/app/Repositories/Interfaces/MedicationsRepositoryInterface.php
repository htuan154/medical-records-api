<?php

namespace App\Repositories\Interfaces;

interface MedicationsRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;
    public function byTherapeuticClass(string $class, int $limit = 50, int $skip = 0): array;
    public function byExpiryRange(string $startIso, string $endIso, int $limit = 50, int $skip = 0): array;
    public function lowStock(int $threshold, int $limit = 50, int $skip = 0): array;
    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byName(string $q, int $limit = 50, int $skip = 0): array;     // medications/by_name
    public function byBarcode(string $barcode, int $limit = 50, int $skip = 0): array; // medications/by_barcode
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array; // medications/by_status
}
