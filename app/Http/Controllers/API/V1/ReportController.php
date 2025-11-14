<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\PatientService;
use App\Services\CouchDB\DoctorService;
use App\Services\CouchDB\MedicalRecordService;
use App\Services\CouchDB\InvoiceService;
use App\Services\CouchDB\AppointmentService;
use App\Services\CouchDB\MedicationService;
use App\Services\CouchDB\TreatmentService;
use App\Services\CouchDB\ConsultationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ReportController extends Controller
{
    public function __construct(
        private PatientService $patientService,
        private DoctorService $doctorService,
        private MedicalRecordService $medicalRecordService,
        private InvoiceService $invoiceService,
        private AppointmentService $appointmentService,
        private MedicationService $medicationService,
        private TreatmentService $treatmentService,
        private ConsultationService $consultationService
    ) {}

    /**
     * Get dashboard statistics
     * GET /api/reports/dashboard
     */
    public function getDashboardStats(Request $request)
    {
        try {
            // Debug: Log user info
            $auth = $request->attributes->get('auth');
            Log::info('Dashboard access attempt', [
                'user_id' => $auth['sub'] ?? 'unknown',
                'auth_data' => $auth
            ]);

            // Lấy dữ liệu từ các service với limit cao để lấy hết
            $patients = $this->patientService->list(1000);
            $doctors = $this->doctorService->list(1000);
            $records = $this->medicalRecordService->list(1000);
            
            // Thử lấy invoices, fallback nếu fail
            try {
                $invoices = $this->invoiceService->list(1000);
            } catch (Exception $e) {
                $invoices = ['rows' => []];
            }

            // Extract data từ response format
            $patientsData = $patients['rows'] ?? $patients['data'] ?? [];
            $doctorsData = $doctors['rows'] ?? $doctors['data'] ?? [];
            $recordsData = $records['rows'] ?? $records['data'] ?? [];
            $invoicesData = $invoices['rows'] ?? $invoices['data'] ?? [];

            // Tính toán thống kê
            $totalPatients = count($patientsData);
            $totalDoctors = count($doctorsData);
            $totalRecords = count($recordsData);
            
            // Tính tổng doanh thu
            $totalRevenue = 0;
            foreach ($invoicesData as $invoice) {
                $invoiceDoc = $invoice['doc'] ?? $invoice;
                $totalRevenue += floatval($invoiceDoc['total_amount'] ?? $invoiceDoc['amount'] ?? 0);
            }

            // Nếu không có hóa đơn, tính doanh thu ước lượng
            if ($totalRevenue == 0 && $totalRecords > 0) {
                $totalRevenue = $totalRecords * 500000; // 500k/lần khám
            }

            return response()->json([
                'total_patients' => $totalPatients,
                'total_doctors' => $totalDoctors,
                'total_records' => $totalRecords,
                'revenue' => $totalRevenue,
                'data_source' => 'database'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to load dashboard stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient statistics
     * GET /api/reports/patient-stats
     */
    public function getPatientStats(Request $request)
    {
        try {
            $patientsResult = $this->patientService->list(1000);
            $patients = $patientsResult['rows'] ?? $patientsResult['data'] ?? [];
            
            // Group by age ranges
            $ageGroups = [
                '0-18' => 0,
                '19-35' => 0,
                '36-50' => 0,
                '51-65' => 0,
                '65+' => 0
            ];

            // Group by gender
            $genderStats = [
                'Nam' => 0,
                'Nữ' => 0,
                'Khác' => 0
            ];

            foreach ($patients as $patientItem) {
                $patient = $patientItem['doc'] ?? $patientItem;
                
                // Age grouping
                $age = $this->calculateAge($patient['birth_date'] ?? null);
                if ($age <= 18) $ageGroups['0-18']++;
                elseif ($age <= 35) $ageGroups['19-35']++;
                elseif ($age <= 50) $ageGroups['36-50']++;
                elseif ($age <= 65) $ageGroups['51-65']++;
                else $ageGroups['65+']++;

                // Gender grouping
                $gender = $patient['gender'] ?? 'Khác';
                if (in_array($gender, ['Nam', 'Nữ'])) {
                    $genderStats[$gender]++;
                } else {
                    $genderStats['Khác']++;
                }
            }

            return response()->json([
                'age_distribution' => $ageGroups,
                'gender_distribution' => $genderStats,
                'total_patients' => count($patients)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to load patient stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue statistics
     * GET /api/reports/revenue-stats
     */
    public function getRevenueStats(Request $request)
    {
        try {
            $recordsResult = $this->medicalRecordService->list(1000);
            $records = $recordsResult['rows'] ?? $recordsResult['data'] ?? [];
            
            // Thử lấy invoices
            try {
                $invoicesResult = $this->invoiceService->list(1000);
                $invoices = $invoicesResult['rows'] ?? $invoicesResult['data'] ?? [];
            } catch (Exception $e) {
                $invoices = [];
            }
            
            $monthlyRevenue = [];
            
            // Nếu có hóa đơn thực
            if (count($invoices) > 0) {
                foreach ($invoices as $invoiceItem) {
                    $invoice = $invoiceItem['doc'] ?? $invoiceItem;
                    $date = $invoice['created_at'] ?? $invoice['invoice_date'] ?? date('Y-m-d');
                    $month = date('Y-m', strtotime($date));
                    
                    if (!isset($monthlyRevenue[$month])) {
                        $monthlyRevenue[$month] = 0;
                    }
                    
                    $monthlyRevenue[$month] += floatval($invoice['total_amount'] ?? $invoice['amount'] ?? 0);
                }
            } else {
                // Ước lượng từ medical records
                foreach ($records as $recordItem) {
                    $record = $recordItem['doc'] ?? $recordItem;
                    $date = $record['created_at'] ?? date('Y-m-d');
                    $month = date('Y-m', strtotime($date));
                    
                    if (!isset($monthlyRevenue[$month])) {
                        $monthlyRevenue[$month] = 0;
                    }
                    
                    $monthlyRevenue[$month] += 500000; // 500k/khám
                }
            }
            
            // Convert to array format
            $result = [];
            foreach ($monthlyRevenue as $month => $revenue) {
                $result[] = [
                    'period' => $month,
                    'revenue' => $revenue
                ];
            }
            
            // Sort by month
            usort($result, function($a, $b) {
                return strcmp($a['period'], $b['period']);
            });
            
            return response()->json($result);
            
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to load revenue stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Advanced revenue summary filtered by date range and patient age
     * GET /api/reports/revenue-advanced
     */
    public function getAdvancedRevenueStats(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $minAge = (int) $request->query('min_age', 40);

        if (!$startDate || !$endDate) {
            return response()->json([
                'error' => 'invalid_date_range',
                'message' => 'Vui lòng cung cấp đầy đủ ngày bắt đầu và ngày kết thúc'
            ], 422);
        }

        try {
            $normalizedStart = $this->buildDateBoundary($startDate, true);
            $normalizedEnd = $this->buildDateBoundary($endDate, false);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'invalid_date_format',
                'message' => 'Định dạng ngày không hợp lệ. Vui lòng sử dụng YYYY-MM-DD'
            ], 422);
        }

        if (strcmp($normalizedStart, $normalizedEnd) > 0) {
            return response()->json([
                'error' => 'invalid_date_range',
                'message' => 'Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc'
            ], 422);
        }

        try {
            $invoiceResult = $this->invoiceService->list(1000, 0, [
                'start' => $normalizedStart,
                'end' => $normalizedEnd
            ]);
            $invoiceRows = $invoiceResult['rows'] ?? $invoiceResult['data'] ?? [];

            $patientResult = $this->patientService->list(5000);
            $patientRows = $patientResult['rows'] ?? $patientResult['data'] ?? [];

            $patientsById = [];
            foreach ($patientRows as $patientRow) {
                $patientDoc = $patientRow['doc'] ?? $patientRow;
                if (!isset($patientDoc['_id'])) {
                    continue;
                }
                $patientsById[$patientDoc['_id']] = $patientDoc;
            }

            $matchedInvoices = [];
            $totalRevenue = 0;
            $uniquePatients = [];

            foreach ($invoiceRows as $invoiceRow) {
                $invoice = $invoiceRow['doc'] ?? $invoiceRow;
                $patientId = $invoice['patient_id'] ?? null;
                if (!$patientId || !isset($patientsById[$patientId])) {
                    continue;
                }

                $patientDoc = $patientsById[$patientId];
                $birthDate = $patientDoc['birth_date']
                    ?? ($patientDoc['personal_info']['birth_date'] ?? null);
                if (!$birthDate) {
                    continue; // Không đủ thông tin tuổi
                }

                $age = $this->calculateAge($birthDate);
                if ($age < $minAge) {
                    continue;
                }

                $amount = $this->extractInvoiceAmount($invoice);
                if ($amount <= 0) {
                    continue;
                }

                $invoiceDate = $invoice['invoice_info']['invoice_date']
                    ?? $invoice['created_at']
                    ?? null;

                $matchedInvoices[] = [
                    'invoice_id' => $invoice['_id'] ?? null,
                    'invoice_number' => $invoice['invoice_info']['invoice_number']
                        ?? $invoice['invoice_number']
                        ?? null,
                    'invoice_date' => $invoiceDate,
                    'patient_id' => $patientId,
                    'patient_name' => $patientDoc['personal_info']['full_name']
                        ?? $patientDoc['full_name']
                        ?? $patientDoc['name']
                        ?? 'Không rõ',
                    'patient_age' => $age,
                    'total_amount' => $amount,
                    'payment_status' => $invoice['payment_status'] ?? null
                ];

                $totalRevenue += $amount;
                $uniquePatients[$patientId] = true;
            }

            usort($matchedInvoices, function ($a, $b) {
                return strcmp($b['invoice_date'] ?? '', $a['invoice_date'] ?? '');
            });

            return response()->json([
                'start_date' => substr($normalizedStart, 0, 10),
                'end_date' => substr($normalizedEnd, 0, 10),
                'min_age' => $minAge,
                'invoice_count' => count($matchedInvoices),
                'patient_count' => count($uniquePatients),
                'total_revenue' => $totalRevenue,
                'currency' => 'VND',
                'invoices' => $matchedInvoices
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'failed_to_calculate_revenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get appointment statistics  
     * GET /api/reports/appointment-stats
     */
    public function getAppointmentStats(Request $request)
    {
        try {
            $appointmentsResult = $this->appointmentService->list(1000);
            $appointments = $appointmentsResult['rows'] ?? $appointmentsResult['data'] ?? [];
            
            $dailyStats = [];
            $now = new \DateTime();
            
            // Get last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = clone $now;
                $date->sub(new \DateInterval("P{$i}D"));
                $dateStr = $date->format('Y-m-d');
                
                $dailyStats[$dateStr] = [
                    'total_appointments' => 0,
                    'completed' => 0,
                    'cancelled' => 0
                ];
            }
            
            foreach ($appointments as $appointmentItem) {
                $appointment = $appointmentItem['doc'] ?? $appointmentItem;
                $appDate = date('Y-m-d', strtotime($appointment['appointment_date'] ?? ''));
                
                if (isset($dailyStats[$appDate])) {
                    $dailyStats[$appDate]['total_appointments']++;
                    
                    $status = $appointment['status'] ?? 'pending';
                    if ($status === 'completed') {
                        $dailyStats[$appDate]['completed']++;
                    } elseif ($status === 'cancelled') {
                        $dailyStats[$appDate]['cancelled']++;
                    }
                }
            }
            
            $result = [];
            foreach ($dailyStats as $date => $stats) {
                $result[] = array_merge(['date' => $date], $stats);
            }
            
            return response()->json($result);
            
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to load appointment stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get medication statistics
     * GET /api/reports/medication-stats  
     */
    public function getMedicationStats(Request $request)
    {
        try {
            $medicationsResult = $this->medicationService->list(1000);
            $medications = $medicationsResult['rows'] ?? $medicationsResult['data'] ?? [];
            
            $treatmentsResult = $this->treatmentService->list(1000);
            $treatments = $treatmentsResult['rows'] ?? $treatmentsResult['data'] ?? [];
            
            $medicationCount = [];
            
            // Count từ medications table
            foreach ($medications as $medItem) {
                $med = $medItem['doc'] ?? $medItem;
                $name = $med['medication_name'] ?? $med['name'] ?? 'Unknown';
                if (!isset($medicationCount[$name])) {
                    $medicationCount[$name] = [
                        'usage_count' => 0,
                        'total_quantity' => 0
                    ];
                }
                $medicationCount[$name]['usage_count']++;
                $medicationCount[$name]['total_quantity'] += intval($med['quantity'] ?? 10);
            }
            
            // Count từ treatments
            foreach ($treatments as $treatmentItem) {
                $treatment = $treatmentItem['doc'] ?? $treatmentItem;
                $meds = $treatment['medications'] ?? $treatment['prescriptions'] ?? [];
                if (is_array($meds)) {
                    foreach ($meds as $med) {
                        $name = $med['medication_name'] ?? $med['name'] ?? 'Unknown';
                        if (!isset($medicationCount[$name])) {
                            $medicationCount[$name] = [
                                'usage_count' => 0,
                                'total_quantity' => 0
                            ];
                        }
                        $medicationCount[$name]['usage_count']++;
                        $medicationCount[$name]['total_quantity'] += intval($med['quantity'] ?? 10);
                    }
                }
            }
            
            // Convert to array and sort
            $result = [];
            foreach ($medicationCount as $name => $stats) {
                $result[] = [
                    'medication_name' => $name,
                    'usage_count' => $stats['usage_count'],
                    'total_quantity' => $stats['total_quantity']
                ];
            }
            
            // Sort by usage count
            usort($result, function($a, $b) {
                return $b['usage_count'] - $a['usage_count'];
            });
            
            return response()->json(array_slice($result, 0, 10)); // Top 10
            
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to load medication stats', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get disease statistics
     * GET /api/reports/disease-stats
     */
    public function getDiseaseStats(Request $request)
    {
        try {
            $recordsResult = $this->medicalRecordService->list(1000);
            $records = $recordsResult['rows'] ?? $recordsResult['data'] ?? [];
            
            $consultationsResult = $this->consultationService->list(1000);
            $consultations = $consultationsResult['rows'] ?? $consultationsResult['data'] ?? [];
            
            $diseaseCount = [];
            
            // Count from medical records
            foreach ($records as $recordItem) {
                $record = $recordItem['doc'] ?? $recordItem;
                $diagnosis = $record['diagnosis'] ?? $record['disease'] ?? 'Unknown';
                if (!isset($diseaseCount[$diagnosis])) {
                    $diseaseCount[$diagnosis] = 0;
                }
                $diseaseCount[$diagnosis]++;
            }
            
            // Count from consultations
            foreach ($consultations as $consultationItem) {
                $consultation = $consultationItem['doc'] ?? $consultationItem;
                $diagnosis = $consultation['diagnosis'] ?? $consultation['disease'] ?? 'Unknown';
                if (!isset($diseaseCount[$diagnosis])) {
                    $diseaseCount[$diagnosis] = 0;
                }
                $diseaseCount[$diagnosis]++;
            }
            
            // Convert to array and sort
            $result = [];
            foreach ($diseaseCount as $disease => $count) {
                $result[] = [
                    'disease_name' => $disease,
                    'case_count' => $count
                ];
            }
            
            // Sort by case count
            usort($result, function($a, $b) {
                return $b['case_count'] - $a['case_count'];
            });
            
            return response()->json(array_slice($result, 0, 10)); // Top 10
            
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to load disease stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to calculate age from birth date
     */
    private function buildDateBoundary(string $date, bool $isStart = true): string
    {
        $dateTime = new \DateTime($date, new \DateTimeZone('UTC'));
        if ($isStart) {
            $dateTime->setTime(0, 0, 0);
        } else {
            $dateTime->setTime(23, 59, 59);
        }
        return $dateTime->format('Y-m-d\TH:i:s\Z');
    }

    private function extractInvoiceAmount(array $invoice): float
    {
        $paymentInfo = $invoice['payment_info'] ?? null;
        if (is_string($paymentInfo)) {
            $decoded = json_decode($paymentInfo, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $paymentInfo = $decoded;
            }
        }

        if (is_array($paymentInfo)) {
            $rawAmount = $paymentInfo['total_amount']
                ?? $paymentInfo['patient_payment']
                ?? $paymentInfo['subtotal']
                ?? 0;
        } else {
            $rawAmount = $invoice['total_amount']
                ?? $invoice['amount']
                ?? $invoice['payment_amount']
                ?? 0;
        }

        if (is_string($rawAmount)) {
            $rawAmount = preg_replace('/[^\d\.\-]/', '', $rawAmount);
        }

        return floatval($rawAmount);
    }

    private function calculateAge($birthDate)
    {
        if (!$birthDate) return 25; // default age
        
        try {
            $birth = new \DateTime($birthDate);
            $today = new \DateTime();
            $age = $today->diff($birth)->y;
            return $age;
        } catch (Exception $e) {
            return 25; // default if can't parse
        }
    }
}
