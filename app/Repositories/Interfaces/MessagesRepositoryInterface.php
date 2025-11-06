<?php

namespace App\Repositories\Interfaces;

interface MessagesRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;

    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byConsultation(string $consultationId, int $limit = 100, int $skip = 0): array;
    public function bySender(string $senderId, int $limit = 50, int $skip = 0): array;
}
