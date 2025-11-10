<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\PatientController;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Controllers\API\V1\DoctorController;
use App\Http\Controllers\API\V1\AppointmentController;
use App\Http\Controllers\API\V1\InvoiceController;
use App\Http\Controllers\API\V1\MedicalRecordController;
use App\Http\Controllers\API\V1\MedicalTestController;
use App\Http\Controllers\API\V1\MedicationController;
use App\Http\Controllers\API\V1\RoleController;
use App\Http\Controllers\API\V1\TreatmentController;
use App\Http\Controllers\API\V1\StaffController;
use App\Http\Controllers\API\V1\ConsultationController;
use App\Http\Controllers\API\V1\MessageController;

Route::prefix('v1')->group(function () {
    // PUBLIC AUTH routes (không cần authentication)
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // PUBLIC REGISTRATION routes (không cần token cho đăng ký)
    Route::post('/users',      [UserController::class, 'store']);
    Route::post('/patients',   [PatientController::class, 'store']);
    
    // PUBLIC STAFF routes for testing
    Route::middleware('log.request')->group(function () {
        Route::get('/staffs-public',       [StaffController::class, 'index']);
        Route::post('/staffs-public',      [StaffController::class, 'store']);
        Route::get('/staffs-public/{id}',  [StaffController::class, 'show']);
        Route::put('/staffs-public/{id}',  [StaffController::class, 'update']);
        Route::delete('/staffs-public/{id}', [StaffController::class, 'destroy']);
        
        // PUBLIC USER routes for testing
        Route::get('/users-public',       [UserController::class, 'index']);
        Route::post('/users-public',      [UserController::class, 'store']);
        Route::get('/users-public/{id}',  [UserController::class, 'show']);
        Route::put('/users-public/{id}',  [UserController::class, 'update']);
        Route::delete('/users-public/{id}', [UserController::class, 'destroy']);
        
        // 🔍 PUBLIC APPOINTMENTS route for debugging
        Route::get('/appointments-public', [AppointmentController::class, 'index']);
    });

    // TẤT CẢ ROUTES KHÁC PHẢI CÓ JWT AUTHENTICATION
    Route::middleware('jwt')->group(function () {
        // AUTH routes (cần authentication)
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // PATIENTS - giữ nguyên routes gốc + report (POST đã chuyển ra ngoài để public)
        Route::get('/patients-report', [PatientController::class, 'report']);
        Route::get('/patients',       [PatientController::class, 'index']);
        Route::get('/patients/{id}',  [PatientController::class, 'show']);
        Route::put('/patients/{id}',  [PatientController::class, 'update']);
        Route::delete('/patients/{id}', [PatientController::class, 'destroy']);

        // USERS - ✅ Đặt specific routes TRƯỚC dynamic routes (POST đã chuyển ra ngoài để public)
        Route::get('/users/available-patients', [UserController::class, 'availablePatients']);
        Route::get('/users/available-doctors', [UserController::class, 'availableDoctors']);
        Route::get('/users/available-staffs', [UserController::class, 'availableStaffs']);
        Route::get('/users',        [UserController::class, 'index']);
        Route::get('/users/{id}',   [UserController::class, 'show']);
        Route::put('/users/{id}',   [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // DOCTORS
        Route::get('/doctors',        [DoctorController::class, 'index']);
        Route::post('/doctors',       [DoctorController::class, 'store']);
        Route::get('/doctors/{id}',   [DoctorController::class, 'show']);
        Route::put('/doctors/{id}',   [DoctorController::class, 'update']);
        Route::delete('/doctors/{id}', [DoctorController::class, 'destroy']);

        // APPOINTMENTS
        Route::get('/appointments',        [AppointmentController::class, 'index']);
        Route::post('/appointments',       [AppointmentController::class, 'store']);
        Route::get('/appointments/{id}',   [AppointmentController::class, 'show']);
        Route::put('/appointments/{id}',   [AppointmentController::class, 'update']);
        Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);

        // INVOICES
        Route::get('/invoices',        [InvoiceController::class, 'index']);
        Route::post('/invoices',       [InvoiceController::class, 'store']);
        Route::get('/invoices/{id}/download', [InvoiceController::class, 'download']);
        Route::get('/invoices/{id}',   [InvoiceController::class, 'show']);
        Route::put('/invoices/{id}',   [InvoiceController::class, 'update']);
        Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy']);

        // MEDICAL RECORDS
        Route::get('/medical-records',        [MedicalRecordController::class, 'index']);
        Route::post('/medical-records',       [MedicalRecordController::class, 'store']);
        Route::get('/medical-records/{id}',   [MedicalRecordController::class, 'show']);
        Route::put('/medical-records/{id}',   [MedicalRecordController::class, 'update']);
        Route::delete('/medical-records/{id}', [MedicalRecordController::class, 'destroy']);

        // MEDICAL TESTS
        Route::get('/medical-tests',        [MedicalTestController::class, 'index']);
        Route::post('/medical-tests',       [MedicalTestController::class, 'store']);
        Route::get('/medical-tests/{id}',   [MedicalTestController::class, 'show']);
        Route::put('/medical-tests/{id}',   [MedicalTestController::class, 'update']);
        Route::delete('/medical-tests/{id}', [MedicalTestController::class, 'destroy']);

        // MEDICATIONS - bao gồm cả stock management
        Route::get('/medications',        [MedicationController::class, 'index']);
        Route::post('/medications',       [MedicationController::class, 'store']);
        Route::get('/medications/{id}',   [MedicationController::class, 'show']);
        Route::put('/medications/{id}',   [MedicationController::class, 'update']);
        Route::delete('/medications/{id}', [MedicationController::class, 'destroy']);
        Route::post('/medications/{id}/stock-increase', [MedicationController::class, 'stockIncrease']);
        Route::post('/medications/{id}/stock-decrease', [MedicationController::class, 'stockDecrease']);

        // ROLES
        Route::get('/roles',        [RoleController::class, 'index']);
        Route::post('/roles',       [RoleController::class, 'store']);
        Route::get('/roles/{id}',   [RoleController::class, 'show']);
        Route::put('/roles/{id}',   [RoleController::class, 'update']);
        Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

        // TREATMENTS
        Route::get('/treatments',        [TreatmentController::class, 'index']);
        Route::post('/treatments',       [TreatmentController::class, 'store']);
        Route::get('/treatments/{id}',   [TreatmentController::class, 'show']);
        Route::put('/treatments/{id}',   [TreatmentController::class, 'update']);
        Route::delete('/treatments/{id}', [TreatmentController::class, 'destroy']);

        // STAFFS
        Route::get('/staffs',       [StaffController::class, 'index']);
        Route::post('/staffs',      [StaffController::class, 'store']);
        Route::get('/staffs/{id}',  [StaffController::class, 'show']);
        Route::put('/staffs/{id}',  [StaffController::class, 'update']);
        Route::delete('/staffs/{id}', [StaffController::class, 'destroy']);

        // CONSULTATIONS (Customer Support Chat)
        Route::get('/consultations',              [ConsultationController::class, 'index']);
        Route::post('/consultations',             [ConsultationController::class, 'store']);
        Route::get('/consultations/{id}',         [ConsultationController::class, 'show']);
        Route::put('/consultations/{id}',         [ConsultationController::class, 'update']);
        Route::delete('/consultations/{id}',      [ConsultationController::class, 'destroy']);
        Route::post('/consultations/{id}/assign', [ConsultationController::class, 'assign']);
        Route::post('/consultations/{id}/close',  [ConsultationController::class, 'close']);

        // MESSAGES (Chat Messages)
        Route::get('/messages',                              [MessageController::class, 'index']);
        Route::post('/messages',                             [MessageController::class, 'store']);
        Route::get('/messages/{id}',                         [MessageController::class, 'show']);
        Route::put('/messages/{id}',                         [MessageController::class, 'update']);
        Route::delete('/messages/{id}',                      [MessageController::class, 'destroy']);
        Route::post('/messages/mark-read',                   [MessageController::class, 'markAsRead']);
        Route::get('/consultations/{consultationId}/messages', [MessageController::class, 'byConsultation']);
    });
    Route::get('/docs', fn () => redirect('/api/documentation'));
});
Route::get('/ping', fn() => response()->json(['ok' => true]));
Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]));
Route::fallback(function () {
    return response()->json([
        'message' => 'Route not found.'
    ], 404);
});
