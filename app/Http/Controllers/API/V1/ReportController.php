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
use DateInterval;
use DatePeriod;
use DateTime;
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
            if (is_object($auth)) {
                $auth = (array) $auth;
            }
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
            [$startDate, $endDate] = $this->normalizeDateRange(
                $request->query('start_date'),
                $request->query('end_date')
            );

            $invoiceResult = $this->invoiceService->list(2000);
            $invoiceRows = $invoiceResult['rows'] ?? $invoiceResult['data'] ?? [];

            $patientResult = $this->patientService->list(5000);
            $patientRows = $patientResult['rows'] ?? $patientResult['data'] ?? [];

            $patientBirthMap = [];
            foreach ($patientRows as $patientRow) {
                $patient = $patientRow['doc'] ?? $patientRow;
                if (!isset($patient['_id'])) {
                    continue;
                }
                $patientBirthMap[$patient['_id']] = $patient['birth_date']
                    ?? ($patient['personal_info']['birth_date'] ?? null);
            }

            $monthlyTotals = [];
            $over40 = 0;
            $under40 = 0;
            $totalRevenue = 0;

            foreach ($invoiceRows as $invoiceRow) {
                $invoice = $invoiceRow['doc'] ?? $invoiceRow;

                $invoiceDateValue = $this->resolveInvoiceDate($invoice);
                if (!$invoiceDateValue) {
                    continue;
                }

                $invoiceDate = new DateTime($invoiceDateValue);
                if ($invoiceDate < $startDate || $invoiceDate > $endDate) {
                    continue;
                }

                $amount = $this->extractInvoiceAmount($invoice);
                if ($amount <= 0) {
                    continue;
                }

                $monthKey = $invoiceDate->format('Y-m');
                $monthlyTotals[$monthKey] = ($monthlyTotals[$monthKey] ?? 0) + $amount;
                $totalRevenue += $amount;

                $patientId = $invoice['patient_id'] ?? null;
                if ($patientId && isset($patientBirthMap[$patientId])) {
                    $age = $this->calculateAge($patientBirthMap[$patientId]);
                    if ($age >= 40) {
                        $over40++;
                    } else {
                        $under40++;
                    }
                }
            }

            $monthlyData = [];
            $monthPeriod = new DatePeriod(
                (clone $startDate)->modify('first day of this month'),
                new DateInterval('P1M'),
                (clone $endDate)->modify('first day of next month')
            );

            foreach ($monthPeriod as $month) {
                $key = $month->format('Y-m');
                $monthlyData[] = [
                    'month' => $key,
                    'label' => $month->format('m/Y'),
                    'revenue' => round($monthlyTotals[$key] ?? 0, 2)
                ];
            }

            $highest = null;
            $lowest = null;
            foreach ($monthlyData as $entry) {
                if ($highest === null || $entry['revenue'] > $highest['revenue']) {
                    $highest = $entry;
                }
                if ($lowest === null || $entry['revenue'] < $lowest['revenue']) {
                    $lowest = $entry;
                }
            }

            $trendDirection = 'flat';
            if (count($monthlyData) >= 2) {
                $first = $monthlyData[0]['revenue'];
                $last = $monthlyData[count($monthlyData) - 1]['revenue'];
                if ($last > $first) {
                    $trendDirection = 'up';
                } elseif ($last < $first) {
                    $trendDirection = 'down';
                }
            }

            $ageTotal = max(1, $over40 + $under40);
            $ageDistribution = [
                'over_40' => [
                    'label' => '>= 40 tuổi',
                    'count' => $over40,
                    'percentage' => round(($over40 / $ageTotal) * 100, 2)
                ],
                'under_40' => [
                    'label' => '< 40 tuổi',
                    'count' => $under40,
                    'percentage' => round(($under40 / $ageTotal) * 100, 2)
                ]
            ];

            return response()->json([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_revenue' => round($totalRevenue, 2),
                'monthly' => $monthlyData,
                'age_distribution' => $ageDistribution,
                'trend' => [
                    'direction' => $trendDirection,
                    'highest_month' => $highest,
                    'lowest_month' => $lowest
                ]
            ]);

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
            [$startDate, $endDate] = $this->normalizeDateRange(
                $request->query('start_date'),
                $request->query('end_date')
            );

            $appointmentsResult = $this->appointmentService->list(2000);
            $appointments = $appointmentsResult['rows'] ?? $appointmentsResult['data'] ?? [];
            
            $dailyStats = [];
            $typeDistribution = [];
            $totalAppointments = 0;

            $dayPeriod = new DatePeriod(
                (clone $startDate),
                new DateInterval('P1D'),
                (clone $endDate)->modify('+1 day')
            );

            foreach ($dayPeriod as $day) {
                $dailyStats[$day->format('Y-m-d')] = [
                    'total_appointments' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'scheduled' => 0
                ];
            }
            
            foreach ($appointments as $appointmentItem) {
                $appointment = $appointmentItem['doc'] ?? $appointmentItem;
                $appointmentInfo = $appointment['appointment_info'] ?? [];
                $scheduled = $appointmentInfo['scheduled_date'] ?? $appointmentInfo['date'] ?? null;
                $scheduled ??= $appointment['appointment_date'] ?? $appointment['created_at'] ?? null;
                if (!$scheduled) {
                    continue;
                }

                $appointmentDate = new DateTime($scheduled);
                if ($appointmentDate < $startDate || $appointmentDate > $endDate) {
                    continue;
                }

                $dayKey = $appointmentDate->format('Y-m-d');
                
                if (!isset($dailyStats[$dayKey])) {
                    $dailyStats[$dayKey] = [
                        'total_appointments' => 0,
                        'completed' => 0,
                        'cancelled' => 0,
                        'scheduled' => 0
                    ];
                }
                
                $dailyStats[$dayKey]['total_appointments']++;
                $totalAppointments++;
                    
                $status = $appointment['status'] ?? 'scheduled';
                if ($status === 'completed') {
                    $dailyStats[$dayKey]['completed']++;
                } elseif ($status === 'cancelled') {
                    $dailyStats[$dayKey]['cancelled']++;
                } else {
                    $dailyStats[$dayKey]['scheduled']++;
                }

                $typeKey = $appointment['appointment_type']
                    ?? $appointment['type']
                    ?? $appointment['category']
                    ?? 'other';
                $typeDistribution[$typeKey] = ($typeDistribution[$typeKey] ?? 0) + 1;
            }
            
            $result = [];
            foreach ($dailyStats as $date => $stats) {
                $result[] = array_merge(['date' => $date], $stats);
            }

            $typeChart = [];
            $distributionTotal = array_sum($typeDistribution) ?: 1;
            foreach ($typeDistribution as $type => $count) {
                $typeChart[] = [
                    'type' => $type,
                    'count' => $count,
                    'percentage' => round(($count / $distributionTotal) * 100, 2)
                ];
            }
            
            return response()->json([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_appointments' => $totalAppointments,
                'daily' => $result,
                'type_distribution' => $typeChart
            ]);
            
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

    private function normalizeDateRange(?string $start = null, ?string $end = null): array
    {
        $endDate = $end ? new DateTime($end) : new DateTime();
        $startDate = $start ? new DateTime($start) : (clone $endDate)->modify('-11 months');

        $startDate->setTime(0, 0, 0);
        $endDate->setTime(23, 59, 59);

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    private function resolveInvoiceDate(array $invoice): ?string
    {
        $invoiceInfo = $invoice['invoice_info']['invoice_date'] ?? null;
        if ($invoiceInfo) {
            return $invoiceInfo;
        }

        return $invoice['invoice_date']
            ?? $invoice['created_at']
            ?? null;
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
