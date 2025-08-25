<?php

namespace App\Repositories\Interfaces;

interface InvoicesRepositoryInterface
{
    public function get(string $id): array;
    public function create(array $data): array;
    public function update(string $id, array $data): array;
    public function delete(string $id, string $rev): array;
    public function byNumber(string $invoiceNumber): array;
    public function byPaymentStatus(string $status, int $limit = 50, int $skip = 0): array;
    public function allFull(int $limit = 50, int $skip = 0): array;
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array; // invoices/by_patient
    public function byRecord(string $recordId, int $limit = 50, int $skip = 0): array;   // invoices/by_record
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array;     // invoices/by_status
    public function byDateRange(string $startIso, string $endIso, int $limit = 100, int $skip = 0): array; // invoices/by_date
}
