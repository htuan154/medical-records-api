<?php

namespace App\Services\CouchDB;

use App\Repositories\CouchDB\PatientRepository;

class ReportService
{
    public function __construct(
        private CouchClient $client,
        private PatientRepository $patients
    ) {}

    /**
     * Tổng quan bệnh nhân từ các view trong _design/patients.
     * - total: tổng số bệnh nhân (đếm qua view by_name)
     * - active: số bệnh nhân đang active (view active_patients)
     * - age_groups: phân bố pediatric/adult/elderly (view by_age_group)
     * - blood_types: phân bố theo nhóm máu (view by_blood_type)
     */
    public function patientsSummary(): array
    {
        // Tổng số qua view by_name (emit chỉ type='patient')
        $totalRows = $this->safeTotalRows(
            $this->patients->view('patients', 'by_name', ['limit' => 0])
        );

        // Active
        $activeRows = $this->safeTotalRows(
            $this->patients->view('patients', 'active_patients', ['limit' => 0])
        );

        // Nhóm tuổi
        $ageGroups = $this->groupCountFromView('patients', 'by_age_group', ['pediatric','adult','elderly']);

        // Nhóm máu
        $bloodTypes = $this->groupCountFromView('patients', 'by_blood_type');

        return [
            'total'        => $totalRows,
            'active'       => $activeRows,
            'age_groups'   => $ageGroups,
            'blood_types'  => $bloodTypes,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Đếm “thô” theo prefix của _id (chỉ hữu ích nếu bạn đặt id kiểu 'patient_***').
     * Nếu không dùng pattern id, bỏ qua hàm này.
     */
    public function roughCountByIdPrefix(string $prefix = 'patient_'): int
    {
        $res = $this->client->db('patients')->allDocs([
            'startkey' => json_encode($prefix),
            'endkey'   => json_encode($prefix . "\ufff0"),
            'limit'    => 0,
        ]);

        return $this->safeTotalRows($res);
    }

    /**
     * Gom số lượng theo key từ một view bất kỳ.
     * - Nếu cung cấp $knownKeys, sẽ đảm bảo những key đó luôn xuất hiện (0 nếu không có).
     * - Nếu view không tồn tại -> trả mảng rỗng hoặc các key known = 0.
     */
    private function groupCountFromView(string $design, string $view, array $knownKeys = []): array
    {
        $rows = $this->patients->view($design, $view, ['limit' => 100000]);
        $counts = [];

        if (!empty($rows['rows']) && is_array($rows['rows'])) {
            foreach ($rows['rows'] as $r) {
                $key = $r['key'] ?? 'unknown';
                // Bỏ qua null key cho gọn
                if ($key === null) {
                    $key = 'unknown';
                }
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        // Bổ sung những key kỳ vọng
        foreach ($knownKeys as $k) {
            if (!array_key_exists($k, $counts)) {
                $counts[$k] = 0;
            }
        }

        ksort($counts);
        return $counts;
    }

    /** Helper: lấy total_rows an toàn từ kết quả CouchDB */
    private function safeTotalRows(array $result): int
    {
        if (isset($result['total_rows']) && is_numeric($result['total_rows'])) {
            return (int) $result['total_rows'];
        }
        if (!empty($result['rows']) && is_array($result['rows'])) {
            return count($result['rows']);
        }
        return 0;
    }
}
