<?php

namespace App\Repositories\CouchDB;

use App\Repositories\Interfaces\PatientsRepositoryInterface;
use App\Services\CouchDB\CouchClient;

class PatientRepository extends BaseCouchRepository implements PatientsRepositoryInterface
{
    protected string $db = 'patients';

    public function __construct(CouchClient $client)
    {
        parent::__construct($client);
    }


    public function get(string $id): array
    {
        return parent::get($id);
    }

    public function create(array $data): array
    {
        return parent::create($data);
    }

    public function update(string $id, array $data): array
    {
        return parent::update($id, $data);
    }

    public function delete(string $id, string $rev): array
    {
        return parent::delete($id, $rev);
    }

    /** Danh sách đầy đủ kèm include_docs */
    public function allFull(int $limit = 50, int $skip = 0): array
    {
        return $this->view('patients', 'all', [
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Tìm theo tên (prefix) – dùng by_name (đã toLowerCase trong map) */
    public function byName(string $q, int $limit = 50, int $skip = 0): array
    {
        $q = mb_strtolower($q);
        return $this->view('patients', 'by_name', [
            'startkey'     => json_encode($q),
            'endkey'       => json_encode($q . "\ufff0"),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Tìm theo số điện thoại – by_phone (exact key match) */
    public function byPhone(string $phone, int $limit = 50, int $skip = 0): array
    {
        return $this->view('patients', 'by_phone', [
            'key'          => json_encode($phone),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Tìm theo số giấy tờ – by_id_number (exact key match) */
    public function byIdNumber(string $idNumber, int $limit = 50, int $skip = 0): array
    {
        return $this->view('patients', 'by_id_number', [
            'key'          => json_encode($idNumber),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Tìm theo nhóm máu – by_blood_type (exact key match) */
    public function byBloodType(string $bloodType, int $limit = 50, int $skip = 0): array
    {
        return $this->view('patients', 'by_blood_type', [
            'key'          => json_encode($bloodType),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Tìm theo nhóm tuổi – by_age_group: pediatric/adult/elderly */
    public function byAgeGroup(string $group, int $limit = 50, int $skip = 0): array
    {
        return $this->view('patients', 'by_age_group', [
            'key'          => json_encode($group),
            'include_docs' => true,
            'limit'        => $limit,
            'skip'         => $skip,
        ]);
    }

    /** Tìm theo trạng thái – nếu status='active' dùng view active_patients */
    public function byStatus(string $status, int $limit = 50, int $skip = 0): array
    {
        if (mb_strtolower($status) === 'active') {
            return $this->view('patients', 'active_patients', [
                'include_docs' => true,
                'limit'        => $limit,
                'skip'         => $skip,
            ]);
        }

        return ['total_rows' => 0, 'rows' => []];
    }
}
