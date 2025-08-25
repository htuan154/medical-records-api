<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\RolesRepository;

class RoleService
{
    public function __construct(
        private CouchClient $client,
        private RolesRepository $repo
    ) {}

    /** Tạo/cập nhật _design/roles với các view: by_name, by_status, by_permission, all */
    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('roles');
        $current = $db->get('_design/roles');

        $views = [
            'by_name' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'role' && doc.name) {
    emit(doc.name.toLowerCase(), null);
  }
}
JS
            ],
            'by_status' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'role' && doc.status) {
    emit(doc.status, null);
  }
}
JS
            ],
            'by_permission' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'role' && Array.isArray(doc.permissions)) {
    for (var i = 0; i < doc.permissions.length; i++) {
      emit(doc.permissions[i], null);
    }
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'role') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['_id' => '_design/roles', 'language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/roles', $doc);
        }
        return $db->create($doc);
    }

    /** List + filter (name/status/permission) */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['name'])) {
            return $this->repo->byName($filters['name']);
        }
        if (!empty($filters['status'])) {
            return $this->repo->byStatus($filters['status'], $limit, $skip);
        }
        if (!empty($filters['permission'])) {
            return $this->repo->byPermission($filters['permission'], $limit, $skip);
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
        $data['type']       = $data['type'] ?? 'role';
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
