<?php

use Illuminate\Support\Facades\Route;
use App\Services\CouchDB\AppointmentService;
use App\Services\CouchDB\PatientService;
use App\Services\CouchDB\DoctorService;
use App\Services\CouchDB\StaffService;
use App\Services\CouchDB\UserService;
use App\Services\CouchDB\TreatmentService;
use App\Services\CouchDB\MedicalRecordService;
use App\Services\CouchDB\InvoiceService;
use App\Services\CouchDB\MedicationService;
use App\Services\CouchDB\MedicalTestService;
use App\Services\CouchDB\RoleService;
use App\Services\CouchDB\ConsultationService;
use App\Services\CouchDB\MessageService;

/**
 * Setup/Migration Routes
 * Chạy một lần sau khi deploy để tạo design documents
 */
Route::prefix('setup')->group(function () {
    
    /**
     * Setup tất cả databases và design documents
     * GET /setup/all
     */
    Route::get('/all', function () {
        $results = [
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
            'databases' => [],
            'services' => []
        ];
        
        // ✅ STEP 1: Tạo tất cả databases trước
        $databases = [
            'appointments', 'patients', 'doctors', 'staff', 'users',
            'treatments', 'medical_records', 'invoices', 'medications',
            'medical_tests', 'roles', 'consultations'
        ];
        
        foreach ($databases as $dbName) {
            try {
                $client = app(\App\Services\CouchDB\CouchClient::class);
                $baseUrl = env('COUCHDB_SCHEME', 'http') . '://' . env('COUCHDB_HOST', '127.0.0.1') . ':' . env('COUCHDB_PORT', 5984);
                $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                    env('COUCHDB_USERNAME', ''),
                    env('COUCHDB_PASSWORD', '')
                )->put("$baseUrl/$dbName");
                
                if ($response->successful() || $response->status() === 412) {
                    // 412 = already exists, đó là OK
                    $results['databases'][$dbName] = 'created_or_exists';
                } else {
                    $results['databases'][$dbName] = ['error' => $response->json()];
                }
            } catch (\Throwable $e) {
                $results['databases'][$dbName] = ['error' => $e->getMessage()];
            }
        }
        
        // ✅ STEP 2: Tạo design documents
        try {
            // Appointments
            try {
                $appointmentService = app(AppointmentService::class);
                $results['services']['appointments'] = $appointmentService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['appointments'] = ['error' => $e->getMessage()];
            }
            
            // Patients
            try {
                $patientService = app(PatientService::class);
                $results['services']['patients'] = $patientService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['patients'] = ['error' => $e->getMessage()];
            }
            
            // Doctors
            try {
                $doctorService = app(DoctorService::class);
                $results['services']['doctors'] = $doctorService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['doctors'] = ['error' => $e->getMessage()];
            }
            
            // Staff
            try {
                $staffService = app(StaffService::class);
                $results['services']['staff'] = $staffService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['staff'] = ['error' => $e->getMessage()];
            }
            
            // Users
            try {
                $userService = app(UserService::class);
                $results['services']['users'] = $userService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['users'] = ['error' => $e->getMessage()];
            }
            
            // Treatments
            try {
                $treatmentService = app(TreatmentService::class);
                $results['services']['treatments'] = $treatmentService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['treatments'] = ['error' => $e->getMessage()];
            }
            
            // Medical Records
            try {
                $medicalRecordService = app(MedicalRecordService::class);
                $results['services']['medical_records'] = $medicalRecordService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['medical_records'] = ['error' => $e->getMessage()];
            }
            
            // Invoices
            try {
                $invoiceService = app(InvoiceService::class);
                $results['services']['invoices'] = $invoiceService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['invoices'] = ['error' => $e->getMessage()];
            }
            
            // Medications
            try {
                $medicationService = app(MedicationService::class);
                $results['services']['medications'] = $medicationService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['medications'] = ['error' => $e->getMessage()];
            }
            
            // Medical Tests
            try {
                $medicalTestService = app(MedicalTestService::class);
                $results['services']['medical_tests'] = $medicalTestService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['medical_tests'] = ['error' => $e->getMessage()];
            }
            
            // Roles
            try {
                $roleService = app(RoleService::class);
                $results['services']['roles'] = $roleService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['roles'] = ['error' => $e->getMessage()];
            }
            
            // Consultations
            try {
                $consultationService = app(ConsultationService::class);
                $results['services']['consultations'] = $consultationService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['consultations'] = ['error' => $e->getMessage()];
            }
            
            // Messages
            try {
                $messageService = app(MessageService::class);
                $results['services']['messages'] = $messageService->ensureDesignDoc();
            } catch (\Throwable $e) {
                $results['services']['messages'] = ['error' => $e->getMessage()];
            }
            
            return response()->json($results, 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
    
    /**
     * Setup appointments design document only
     * GET /setup/appointments
     */
    Route::get('/appointments', function () {
        try {
            $service = app(AppointmentService::class);
            $result = $service->ensureDesignDoc();
            return response()->json([
                'status' => 'success',
                'service' => 'appointments',
                'result' => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
    
    /**
     * Setup patients design document
     * GET /setup/patients
     */
    Route::get('/patients', function () {
        try {
            $service = app(PatientService::class);
            $result = $service->ensureDesignDoc();
            return response()->json([
                'status' => 'success',
                'service' => 'patients',
                'result' => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    });
});
