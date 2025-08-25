<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\DoctorsRepository;

class DoctorService
{
    public function __construct(
        private CouchClient $client,
        private DoctorsRepository $repo
    ) {}

    /** Tạo/cập nhật design doc _design/doctors */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('doctors');
        $current = $db->get('_design/doctors');

        $views = [
            'by_name' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'doctor' && doc.personal_info && doc.personal_info.full_name) {
    emit(doc.personal_info.full_name.toLowerCase(), null);
  }
}
JS
            ],
            'by_specialty' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'doctor' && doc.professional_info && doc.professional_info.specialty) {
    emit(doc.professional_info.specialty, null);
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'doctor') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['_id' => '_design/doctors', 'language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/doctors', $doc);
        }
        return $db->create($doc);
    }

    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['q'])) {
            return $this->repo->ByName($filters['q'], $limit, $skip);
        }
        if (!empty($filters['specialty'])) {
            return $this->repo->bySpecialty($filters['specialty'], $limit, $skip);
        }
        return $this->repo->allFull($limit, $skip);
    }

    public function find(string $id): array
    {
        $doc = $this->repo->get($id);
        if (isset($doc['error']) && $doc['error'] === 'not_found') {
            return ['status' => 404, 'data' => $doc];
        }
        return ['status' => 200, 'data' => $doc];
    }

    public function create(array $data): array
    {
        $data['type']       = $data['type'] ?? 'doctor';
        $data['created_at'] = $data['created_at'] ?? now()->toIso8601String();
        $data['updated_at'] = $data['updated_at'] ?? now()->toIso8601String();
        return $this->repo->create($data);
    }

    public function update(string $id, array $data): array
    {
        $data['_id'] = $id;
        if (empty($data['_rev'])) {
            return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Missing _rev']];
        }
        $data['updated_at'] = now()->toIso8601String();
        $res = $this->repo->update($id, $data);
        return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }

    public function delete(string $id, ?string $rev): array
    {
        if (!$rev) {
            return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Missing rev']];
        }
        $res = $this->repo->delete($id, $rev);
        return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }
}
