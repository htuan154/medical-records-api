<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\PatientRepository;

class PatientService
{
    public function __construct(
        private CouchClient $client,
        private PatientRepository $repo
    ) {}

    /** Tạo/cập nhật design doc _design/patients để tránh not_found view */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('patients');
        $current = $db->get('_design/patients');

        $views = [
            'by_name' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'patient' && doc.personal_info && doc.personal_info.full_name) {
    emit(doc.personal_info.full_name.toLowerCase(), {
      id: doc._id,
      name: doc.personal_info.full_name,
      phone: doc.personal_info.phone,
      birth_date: doc.personal_info.birth_date,
      status: doc.status
    });
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'patient') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = [
            '_id'      => '_design/patients',
            'language' => 'javascript',
            'views'    => $views,
        ];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/patients', $doc);
        }
        return $db->create($doc);
    }

    /** Danh sách + search theo tên (view by_name + include_docs=true) */
    public function list(int $limit = 50, int $skip = 0, ?string $q = null): array
    {
        if ($q !== null && $q !== '') {
            return $this->repo->ByName($q, $limit, $skip);
        }
        return $this->repo->allFull($limit, $skip);
    }

    /** Lấy chi tiết; chuẩn hoá 404 khi không có */
    public function find(string $id): array
    {
        $doc = $this->repo->get($id);
        if (isset($doc['error']) && $doc['error'] === 'not_found') {
            return ['status' => 404, 'data' => $doc];
        }
        return ['status' => 200, 'data' => $doc];
    }

    /** Tạo mới (mặc định type=patient) */
    public function create(array $data): array
    {
        $data['type']       = $data['type']       ?? 'patient';
        $data['created_at'] = $data['created_at'] ?? now()->toIso8601String();
        $data['updated_at'] = $data['updated_at'] ?? now()->toIso8601String();
        return $this->repo->create($data);
    }

    /** Cập nhật – yêu cầu _rev */
    public function update(string $id, array $data): array
    {
        $data['_id'] = $id;
        if (empty($data['_rev'])) {
            return [
                'status' => 409,
                'data'   => ['error' => 'conflict', 'reason' => 'Missing _rev. GET the doc to obtain latest _rev.'],
            ];
        }
        $data['updated_at'] = now()->toIso8601String();

        $res = $this->repo->update($id, $data);
        return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }

    /** Xoá – yêu cầu rev */
    public function delete(string $id, ?string $rev): array
    {
        if (!$rev) {
            return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Missing rev parameter']];
        }
        $res = $this->repo->delete($id, $rev);
        return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }
}
