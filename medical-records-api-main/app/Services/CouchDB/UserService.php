<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\UsersRepository;
use App\Repositories\CouchDB\PatientRepository;

class UserService
{
    public function __construct(
        private CouchClient $client,
        private UsersRepository $repo,
        private PatientRepository $patientRepo
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

        $doc = ['language' => 'javascript', 'views' => $views];

        if (!isset($current['error'])) {
            $doc['_id'] = '_design/users';
            $doc['_rev'] = $current['_rev'];
            return $db->putDesign('_design/users', $doc);
        }
        
        $doc['_id'] = '_design/users';
        return $db->putDesign('_design/users', $doc);
    }

    /** List + filter đơn giản: q=username, staff_id/patient_id */
    public function list(int $limit = 50, int $skip = 0, array $filters = []): array
    {
        // ✅ Support multiple search methods
        if (!empty($filters['username'])) {
            return $this->repo->byUsername($filters['username'], $limit, $skip);
        }
        if (!empty($filters['q'])) {
            // Search by username with q parameter
            return $this->repo->byUsername($filters['q'], $limit, $skip);
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
        // Lấy document hiện tại
        $current = $this->repo->get($id);
        if (isset($current['error']) && $current['error'] === 'not_found') {
            return ['status' => 404, 'data' => $current];
        }

        // Nếu có trường 'password' (plain text) thì hash lại trước khi lưu
        if (!empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);
        }

        // Lấy _rev: ưu tiên từ client, nếu thiếu dùng _rev hiện tại
        $rev = $data['_rev'] ?? ($current['_rev'] ?? null);
        if (!$rev) {
            return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Missing _rev and cannot resolve latest revision']];
        }

        // Merge doc theo kiểu patch, không ghi đè _id
        $merged = $this->mergeDocs($current, $data);
        $merged['_id']       = $id;
        $merged['_rev']      = $rev;
        $merged['type']      = $current['type'] ?? 'user';
        $merged['updated_at']= now()->toIso8601String();

        $res = $this->repo->update($id, $merged);
        if (isset($res['error']) && $res['error'] === 'conflict') {
            // Retry với _rev mới nhất
            $latest = $this->repo->get($id);
            if (!isset($latest['error'])) {
                $merged = $this->mergeDocs($latest, $data);
                $merged['_id']  = $id;
                $merged['_rev'] = $latest['_rev'] ?? null;
                if (!$merged['_rev']) {
                    return ['status' => 409, 'data' => ['error' => 'conflict', 'reason' => 'Cannot resolve latest _rev to retry']];
                }
                $merged['type']       = $latest['type'] ?? 'user';
                $merged['updated_at'] = now()->toIso8601String();
                $res = $this->repo->update($id, $merged);
            }
        }

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
            $result = $this->repo->delete($id, $rev);

            // Check nếu CouchDB trả về error
            if (isset($result['error'])) {
                // Nếu đã bị xóa trước đó
                if ($result['error'] === 'not_found' && ($result['reason'] ?? '') === 'deleted') {
                    return [
                        'status' => 200,
                        'data' => [ 'ok' => true, 'id' => $id, 'message' => 'Document already deleted' ]
                    ];
                }
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

            // Check nếu operation thành công
            if (isset($result['ok']) && $result['ok'] === true) {
                return [
                    'status' => 200,
                    'data' => $result
                ];
            }

            // Trường hợp không xác định được kết quả
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

    /** Đệ quy merge mảng (không ghi đè _id) */
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

    /** Lấy danh sách patients chưa được liên kết với user nào */
    public function getAvailablePatients(int $limit = 50, int $skip = 0): array
    {
        try {
            // 1. Lấy tất cả users có linked_patient_id
            $linkedPatients = [];
            $users = $this->repo->allFull(1000, 0); // Lấy nhiều để đảm bảo có hết
            
            if (isset($users['rows'])) {
                foreach ($users['rows'] as $row) {
                    $userDoc = $row['doc'] ?? [];
                    if (!empty($userDoc['linked_patient_id'])) {
                        $linkedPatients[] = $userDoc['linked_patient_id'];
                    }
                }
            }

            // 2. Lấy tất cả patients
            $allPatients = $this->patientRepo->allFull($limit + count($linkedPatients), $skip);
            
            // 3. Filter out những patients đã được liên kết
            $availablePatients = [];
            if (isset($allPatients['rows'])) {
                foreach ($allPatients['rows'] as $row) {
                    $patientDoc = $row['doc'] ?? [];
                    if (!empty($patientDoc['_id']) && !in_array($patientDoc['_id'], $linkedPatients)) {
                        $availablePatients[] = $row;
                    }
                }
            }

            return [
                'total_rows' => count($availablePatients),
                'offset' => $skip,
                'rows' => array_slice($availablePatients, 0, $limit)
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'fetch_failed',
                'message' => $e->getMessage(),
                'rows' => []
            ];
        }
    }
}
