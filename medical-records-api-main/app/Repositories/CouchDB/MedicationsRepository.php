<?php

namespace App\Repositories\CouchDB;

use App\Repositories\Interfaces\MedicationsRepositoryInterface;
use App\Services\CouchDB\CouchClient;

class MedicationsRepository extends BaseCouchRepository implements MedicationsRepositoryInterface
{
    protected string $db = 'medications';

    public function __construct(CouchClient $client) { parent::__construct($client); }

    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('medications', 'all', [
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byName(string $q, int $limit = 50, int $skip = 0): array
    {
        $q = mb_strtolower($q);
        return $this->view('medications', 'by_name', [
            'startkey' => json_encode($q),
            'endkey'   => json_encode($q . "\ufff0"),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    public function byBarcode(string $barcode, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medications', 'by_barcode', [
            'key' => json_encode($barcode),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        // Cần có view 'medications/by_status' trong _design/medications
        return $this->view('medications', 'by_status', [
            'key'          => json_encode($status),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
    public function byTherapeuticClass(string $class, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medications', 'by_therapeutic_class', [
            'key' => json_encode($class),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** 🔥 mới – dùng expiry_date */
    public function byExpiryRange(string $startIso, string $endIso, int $limit = 50, int $skip = 0): array
    {
        return $this->view('medications', 'by_expiry_date', [
            'startkey' => json_encode($startIso),
            'endkey'   => json_encode($endIso),
            'include_docs' => true, 'limit' => $limit, 'skip' => $skip,
        ]);
    }

    /** 🔥 mới – cần map special phát hiện tồn kho thấp */
    public function lowStock(int $threshold, int $limit = 50, int $skip = 0): array
    {
        // Với CouchDB view, bạn có thể emit current_stock làm key, sau đó query theo range.
        // Ở đây ta giả định đã có view 'by_stock' emit(current_stock, null)
        return $this->view('medications', 'by_stock', [
            'startkey'     => 0,
            'endkey'       => $threshold,
            'inclusive_end'=> true,
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }
}
