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

        $doc = ['language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_id'] = '_design/roles';
            $doc['_rev'] = $current['_rev'];
            return $db->putDesign('_design/roles', $doc);
        }
        
        $doc['_id'] = '_design/roles';
        return $db->putDesign('_design/roles', $doc);
    }

    /** List + filter (name/status/permission) */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        $result = null;
        if (!empty($filters['name'])) {
            $result = $this->repo->byName($filters['name']);
        } elseif (!empty($filters['status'])) {
            $result = $this->repo->byStatus($filters['status'], $limit, $skip);
        } elseif (!empty($filters['permission'])) {
            $result = $this->repo->byPermission($filters['permission'], $limit, $skip);
        } else {
            $result = $this->repo->allFull($limit, $skip);
        }

        // Normalize response for frontend
        $rows = [];
        $total_rows = 0;
        
        if (is_array($result)) {
            if (isset($result['rows']) && is_array($result['rows'])) {
                // Extract documents from CouchDB view response
                $rows = array_map(function($r) {
                    return isset($r['doc']) ? $r['doc'] : (isset($r['value']) ? $r['value'] : $r);
                }, $result['rows']);
                $total_rows = $result['total_rows'] ?? count($rows);
            } elseif (isset($result['items']) && is_array($result['items'])) {
                $rows = $result['items'];
                $total_rows = $result['total'] ?? count($rows);
            } elseif (isset($result['data']) && is_array($result['data'])) {
                $rows = $result['data'];
                $total_rows = $result['total'] ?? count($rows);
            } elseif (is_array($result)) {
                $rows = $result;
                $total_rows = count($rows);
            }
        }
        
        // Only keep type=role documents
        $rows = array_filter($rows, function($item) {
            return is_array($item) && (isset($item['type']) ? $item['type'] === 'role' : true);
        });
        
        return [
            'rows' => array_values($rows),
            'total_rows' => $total_rows
        ];
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
        // Lấy document hiện tại để resolve _rev và merge patch
        $current = $this->repo->get($id);
        if (isset($current['error']) && $current['error'] === 'not_found') {
            return ['status' => 404, 'data' => $current];
        }

        $rev = $data['_rev'] ?? ($current['_rev'] ?? null);
        if (!$rev) {
            return [
                'status' => 409,
                'data' => ['error' => 'conflict', 'reason' => 'Missing _rev and cannot resolve latest revision']
            ];
        }

        $merged = $this->mergeDocs($current, $data);
        $merged['_id']        = $id;
        $merged['_rev']       = $rev;
        $merged['type']       = $current['type'] ?? 'role';
        $merged['updated_at'] = now()->toIso8601String();

        // Thử cập nhật lần 1
        $res = $this->repo->update($id, $merged);
        if (isset($res['error']) && $res['error'] === 'conflict') {
            // Lấy _rev mới nhất và thử lại 1 lần
            $latest = $this->repo->get($id);
            if (!isset($latest['error'])) {
                $merged = $this->mergeDocs($latest, $data);
                $merged['_id']  = $id;
                $merged['_rev'] = $latest['_rev'] ?? null;
                if (!$merged['_rev']) {
                    return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Cannot resolve latest _rev to retry']];
                }
                $merged['type']       = $latest['type'] ?? 'role';
                $merged['updated_at'] = now()->toIso8601String();
                $res = $this->repo->update($id, $merged);
            }
        }

        return ['status' => (!empty($res['ok']) ? 200 : 400), 'data' => $res];
    }

    public function delete(string $id, ?string $rev): array
    {
        // Nếu thiếu rev -> cố gắng resolve từ doc hiện tại
        if (!$rev) {
            $doc = $this->repo->get($id);
            if (isset($doc['error'])) {
                return [
                    'status' => $doc['error'] === 'not_found' ? 404 : 400,
                    'data'   => $doc,
                ];
            }
            $rev = $doc['_rev'] ?? null;
            if (!$rev) {
                return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Missing rev and cannot resolve latest revision']];
            }
        }

        $result = $this->repo->delete($id, $rev);

        if (isset($result['error'])) {
            // Đã xóa trước đó -> idempotent success
            if ($result['error'] === 'not_found' && (($result['reason'] ?? '') === 'deleted')) {
                return [
                    'status' => 200,
                    'data' => [ 'ok' => true, 'id' => $id, 'message' => 'Document already deleted' ]
                ];
            }

            // Conflict -> lấy latest rev và retry 1 lần
            if ($result['error'] === 'conflict') {
                $latest = $this->repo->get($id);
                if (!isset($latest['error'])) {
                    $retry = $this->repo->delete($id, $latest['_rev'] ?? '');
                    if (isset($retry['ok']) && $retry['ok']) {
                        return ['status' => 200, 'data' => $retry];
                    }
                    $result = $retry; // rơi xuống return chung
                }
            }
        }

        return ['status' => (!empty($result['ok']) ? 200 : (isset($result['error']) && $result['error'] === 'not_found' ? 404 : 400)), 'data' => $result];
    }

    /** Đệ quy merge (không ghi đè _id) */
    private function mergeDocs(array $base, array $changes): array
    {
        foreach ($changes as $k => $v) {
            if ($k === '_id') continue;
            if (is_array($v) && isset($base[$k]) && is_array($base[$k])) {
                $base[$k] = $this->mergeDocs($base[$k], $v);
            } else {
                $base[$k] = $v;
            }
        }
        return $base;
    }
}
