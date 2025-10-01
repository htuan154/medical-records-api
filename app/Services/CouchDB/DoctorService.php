<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\DoctorsRepository;
use App\Repositories\CouchDB\UsersRepository;

class DoctorService
{
    public function __construct(
        private CouchClient $client,
        private DoctorsRepository $repo,
        private UsersRepository $usersRepo  // ✅ Inject UsersRepository
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
            return $this->repo->byName($filters['q'], $limit, $skip);
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
            return [
                'status' => 400,
                'data' => [
                    'error' => 'missing_rev',
                    'reason' => 'Revision parameter is required for delete operation'
                ]
            ];
        }

        try {
            // ✅ BƯỚC 1: Tìm User liên kết với Doctor này
            $linkedUsers = $this->usersRepo->byStaff($id);  // Doctor cũng dùng linked_staff_id

            // ✅ BƯỚC 2: Xóa Doctor trước
            $result = $this->repo->delete($id, $rev);

            // Check nếu xóa Doctor thất bại
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

            // ✅ BƯỚC 3: Nếu xóa Doctor thành công, xóa User liên kết
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
                            // Log lỗi nhưng không fail toàn bộ operation
                            error_log("Failed to delete linked user {$userDoc['_id']}: " . $e->getMessage());
                        }
                    }
                }
            }

            // ✅ BƯỚC 4: Return kết quả với thông tin cascade
            if (isset($result['ok']) && $result['ok'] === true) {
                return [
                    'status' => 200,
                    'data' => [
                        'ok' => true,
                        'id' => $result['id'] ?? $id,
                        'rev' => $result['rev'] ?? null,
                        'cascade_deleted_users' => $deletedUsers,
                        'message' => count($deletedUsers) > 0
                            ? "Doctor và " . count($deletedUsers) . " user liên kết đã được xóa"
                            : "Doctor đã được xóa"
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
