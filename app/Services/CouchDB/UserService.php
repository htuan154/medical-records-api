<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\UsersRepository;

class UserService
{
    public function __construct(
        private CouchClient $client,
        private UsersRepository $repo
    ) {}

    /** Đảm bảo _design/users tồn tại */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('users');
        $current = $db->get('_design/users');

        $views = [
            'by_username' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'user' && doc.username) {
    emit(doc.username.toLowerCase(), null);
  }
}
JS
            ],
            'by_staff' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'user' && doc.account_type === 'staff' && doc.linked_staff_id) {
    emit(doc.linked_staff_id, null);
  }
}
JS
            ],
            'by_patient' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'user' && doc.account_type === 'patient' && doc.linked_patient_id) {
    emit(doc.linked_patient_id, null);
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'user') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['_id' => '_design/users', 'language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/users', $doc);
        }
        return $db->create($doc);
    }

    /** List + filter đơn giản: q=username, staff_id/patient_id */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['username'])) {
            return $this->repo->byUsername($filters['username'], $limit, $skip);
        }
        if (!empty($filters['staff_id'])) {
            return $this->repo->byStaff($filters['staff_id'], $limit, $skip);
        }
        if (!empty($filters['patient_id'])) {
            return $this->repo->byPatient($filters['patient_id'], $limit, $skip);
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
        $data['type']       = $data['type'] ?? 'user';
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
