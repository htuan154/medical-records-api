<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\StaffsRepository;

class StaffService
{
    public function __construct(
        private CouchClient $client,
        private StaffsRepository $repo
    ) {}

    public function ensureDesignDoc(): array
    {
        $db = $this->client->db('staffs');
        $current = $db->get('_design/staffs');

        $views = [
            'by_name' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'staff' && doc.full_name) {
    emit(doc.full_name.toLowerCase(), null);
  }
}
JS
            ],
            'by_type' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'staff' && doc.staff_type) {
    emit(doc.staff_type, null);
  }
}
JS
            ],
            'by_department' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'staff' && doc.department) {
    emit(doc.department, null);
  }
}
JS
            ],
            'by_status' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'staff' && doc.status) {
    emit(doc.status, null);
  }
}
JS
            ],
            'by_day' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'staff' && doc.shift && Array.isArray(doc.shift.days)) {
    for (var i=0; i<doc.shift.days.length; i++) {
      emit(doc.shift.days[i], null);
    }
  }
}
JS
            ],
            'all' => [
                'map' => <<<JS
function (doc) {
  if (doc.type === 'staff') emit(doc._id, null);
}
JS
            ],
        ];

        $doc = ['_id' => '_design/staffs', 'language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_rev'] = $current['_rev'];
            return $db->put('_design/staffs', $doc);
        }
        return $db->create($doc);
    }

    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        if (!empty($filters['staff_type'])) return $this->repo->byType($filters['staff_type'], $limit, $skip);
        if (!empty($filters['department'])) return $this->repo->byDepartment($filters['department'], $limit, $skip);
        if (!empty($filters['status']))     return $this->repo->byStatus($filters['status'], $limit, $skip);
        if (!empty($filters['day']))        return $this->repo->byDay($filters['day'], $limit, $skip);
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
        $data['type']       = $data['type'] ?? 'staff';
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
