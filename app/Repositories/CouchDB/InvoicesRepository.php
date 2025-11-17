<?php

namespace App\Repositories\CouchDB;

use App\Repositories\Interfaces\InvoicesRepositoryInterface;
use App\Services\CouchDB\CouchClient;

class InvoicesRepository extends BaseCouchRepository implements InvoicesRepositoryInterface
{
    protected string $db = 'invoices';

    public function __construct(CouchClient $client) { parent::__construct($client); }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('invoices', 'all', [
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byRecord(string $recordId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('invoices', 'by_record', [
            'key' => json_encode($recordId),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('invoices', 'by_status', [
            'key' => json_encode($status),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** BỔ SUNG: theo bệnh nhân */
    public function byPatient(string $patientId, int $limit = 50, int $skip = 0): array
    {
        // cần view invoices/by_patient
        return $this->view('invoices', 'by_patient', [
            'key' => json_encode($patientId),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** BỔ SUNG: theo medical record ID */
    public function byMedicalRecord(string $medicalRecordId, int $limit = 50, int $skip = 0): array
    {
        return $this->view('invoices', 'by_medical_record', [
            'key' => json_encode($medicalRecordId),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** BỔ SUNG: theo khoảng ngày hóa đơn */
    public function byDateRange(string $startIso, string $endIso, int $limit = 50, int $skip = 0): array
    {
        // cần view invoices/by_invoice_date emit(invoice_info.invoice_date)
        return $this->view('invoices', 'by_invoice_date', [
            'startkey' => json_encode($startIso),
            'endkey'   => json_encode($endIso),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byNumber(string $invoiceNumber): array
    {

        return $this->view('invoices', 'by_number', [
            'key'          => json_encode($invoiceNumber),
            'include_docs' => true,
            'limit'        => 1,
        ]);
    }

    public function byPaymentStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        return $this->view('invoices', 'by_payment_status', [
            'key'          => json_encode($status),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
}
