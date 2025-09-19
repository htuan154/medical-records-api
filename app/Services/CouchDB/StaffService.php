<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\StaffsRepository;
use App\Repositories\CouchDB\UsersRepository;

class StaffService
{
    public function __construct(
        private CouchClient $client,
        private StaffsRepository $repo,
        private UsersRepository $usersRepo  // ✅ Add this property
    ) {}

    /** Tạo/cập nhật design doc _design/staffs để tránh not_found view */
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

    /** Xoá – yêu cầu rev và validate kết quả */
    public function delete(string $id, ?string $rev): array
    {
        if (!$rev) {
            return [
                'status' => 400,
                'data' => [
                    'error' => 'missing_rev',
                    'reason' => 'Revision parameter is required for delete operation'
                ]
            ];
        }

        try {
            // ✅ Now $this->usersRepo is defined
            $linkedUsers = $this->usersRepo->byStaff($id);

            $result = $this->repo->delete($id, $rev);

            if (isset($result['error'])) {
                $status = match($result['error']) {
                    'not_found' => 404,
                    'conflict' => 409,
                    default => 400
                };

                return [
                    'status' => $status,
                    'data' => $result
                ];
            }

            // ✅ Cascade delete
            $deletedUsers = [];
            if (isset($result['ok']) && $result['ok'] === true && !empty($linkedUsers['rows'])) {
                foreach ($linkedUsers['rows'] as $row) {
                    $userDoc = $row['doc'] ?? $row['value'] ?? $row;
                    if (isset($userDoc['_id']) && isset($userDoc['_rev'])) {
                        try {
                            $userDeleteResult = $this->usersRepo->delete($userDoc['_id'], $userDoc['_rev']);
                            if (isset($userDeleteResult['ok']) && $userDeleteResult['ok']) {
                                $deletedUsers[] = $userDoc['_id'];
                            }
                        } catch (\Exception $e) {
                            error_log("Failed to delete linked user {$userDoc['_id']}: " . $e->getMessage());
                        }
                    }
                }
            }

            if (isset($result['ok']) && $result['ok'] === true) {
                return [
                    'status' => 200,
                    'data' => [
                        'ok' => true,
                        'id' => $result['id'] ?? $id,
                        'rev' => $result['rev'] ?? null,
                        'cascade_deleted_users' => $deletedUsers,
                        'message' => count($deletedUsers) > 0
                            ? "Staff và " . count($deletedUsers) . " user liên kết đã được xóa"
                            : "Staff đã được xóa"
                    ]
                ];
            }

            return [
                'status' => 400,
                'data' => [
                    'error' => 'unknown_result',
                    'reason' => 'Delete operation did not return expected result',
                    'result' => $result
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status' => 500,
                'data' => [
                    'error' => 'delete_failed',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }
}
