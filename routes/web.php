<?php
use Illuminate\Support\Facades\Route;
use App\Services\CouchDB\CouchClient;

Route::get('/', fn() => 'OK');

// 🚀 Setup consultations database
Route::get('/setup-consultations', function () {
    try {
        $client = new CouchClient();
        $db = $client->db('consultations');
        
        // Create design docs
        $consultationService = app(\App\Services\CouchDB\ConsultationService::class);
        $messageService = app(\App\Services\CouchDB\MessageService::class);
        
        $designConsultations = $consultationService->ensureDesignDoc();
        $designMessages = $messageService->ensureDesignDoc();
        
        // Create sample data
        $sampleConsultations = [
            [
                '_id' => 'consultation_2025_001',
                'type' => 'consultation',
                'patient_id' => 'patient_2024_001',
                'patient_info' => [
                    'name' => 'Nguyễn Văn A',
                    'phone' => '0901234567',
                ],
                'status' => 'waiting',
                'last_message' => 'Xin chào, tôi muốn tư vấn về dịch vụ khám sức khỏe tổng quát',
                'last_message_at' => '2025-11-09T09:00:00Z',
                'unread_count_patient' => 0,
                'unread_count_staff' => 1,
                'created_at' => '2025-11-09T09:00:00Z',
                'updated_at' => '2025-11-09T09:00:00Z',
            ],
            [
                '_id' => 'consultation_2025_002',
                'type' => 'consultation',
                'patient_id' => 'patient_2024_002',
                'patient_info' => [
                    'name' => 'Trần Thị B',
                    'phone' => '0902345678',
                ],
                'staff_id' => 'user_admin_001',
                'staff_info' => [
                    'name' => 'Admin',
                ],
                'status' => 'active',
                'last_message' => 'Chúng tôi sẽ hỗ trợ bạn ngay',
                'last_message_at' => '2025-11-09T10:30:00Z',
                'unread_count_patient' => 1,
                'unread_count_staff' => 0,
                'created_at' => '2025-11-09T10:00:00Z',
                'updated_at' => '2025-11-09T10:30:00Z',
            ],
        ];
        
        $sampleMessages = [
            [
                '_id' => 'message_2025_001',
                'type' => 'message',
                'consultation_id' => 'consultation_2025_001',
                'sender_id' => 'patient_2024_001',
                'sender_type' => 'patient',
                'sender_name' => 'Nguyễn Văn A',
                'message' => 'Xin chào, tôi muốn tư vấn về dịch vụ khám sức khỏe tổng quát',
                'is_read' => false,
                'created_at' => '2025-11-09T09:00:00Z',
            ],
            [
                '_id' => 'message_2025_002',
                'type' => 'message',
                'consultation_id' => 'consultation_2025_002',
                'sender_id' => 'patient_2024_002',
                'sender_type' => 'patient',
                'sender_name' => 'Trần Thị B',
                'message' => 'Tôi cần đặt lịch hẹn khám bệnh',
                'is_read' => true,
                'created_at' => '2025-11-09T10:00:00Z',
            ],
            [
                '_id' => 'message_2025_003',
                'type' => 'message',
                'consultation_id' => 'consultation_2025_002',
                'sender_id' => 'user_admin_001',
                'sender_type' => 'staff',
                'sender_name' => 'Admin',
                'message' => 'Chúng tôi sẽ hỗ trợ bạn ngay. Bạn muốn đặt lịch vào thời gian nào?',
                'is_read' => false,
                'created_at' => '2025-11-09T10:30:00Z',
            ],
        ];
        
        $allDocs = array_merge($sampleConsultations, $sampleMessages);
        $bulkResult = $db->bulk($allDocs);
        
        return response()->json([
            'status' => 'success',
            'design_consultations' => $designConsultations,
            'design_messages' => $designMessages,
            'bulk_insert' => $bulkResult,
            'total_docs' => count($allDocs),
        ]);
        
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'failed',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// 🔍 Simple CouchDB test
Route::get('/test-couchdb', function () {
    try {
        $client = new CouchClient();
        
        // Test 1: Ping CouchDB
        $up = $client->up();
        
        // Test 2: Check appointments DB
        $db = $client->db('appointments');
        $allDocs = $db->allDocs(['limit' => 1]);
        
        // Test 3: Get design doc
        $designDoc = $db->get('_design/appointments');
        
        return response()->json([
            'status' => 'success',
            'couchdb_up' => $up,
            'appointments_total' => $allDocs['total_rows'] ?? 0,
            'design_doc_exists' => !isset($designDoc['error']),
            'views' => array_keys($designDoc['views'] ?? [])
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'failed',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

// ✅ Health check với CouchDB connection test
Route::get('/health-detailed', function () {
    $health = [
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'env' => app()->environment(),
        'couchdb' => [
            'configured' => false,
            'connection' => 'unknown',
            'error' => null
        ]
    ];
    
    try {
        $client = new CouchClient();
        $health['couchdb']['configured'] = true;
        $health['couchdb']['host'] = env('COUCHDB_HOST', 'not-set');
        $health['couchdb']['port'] = env('COUCHDB_PORT', 'not-set');
        $health['couchdb']['scheme'] = env('COUCHDB_SCHEME', 'not-set');
        
        // Test connection
        $up = $client->up();
        $health['couchdb']['connection'] = isset($up['status']) && $up['status'] === 'ok' ? 'success' : 'failed';
        $health['couchdb']['response'] = $up;
    } catch (\Throwable $e) {
        $health['status'] = 'degraded';
        $health['couchdb']['connection'] = 'failed';
        $health['couchdb']['error'] = $e->getMessage();
    }
    
    return response()->json($health);
});

// 🔍 Debug endpoint để test appointments trực tiếp
Route::get('/debug/appointments', function () {
    $debug = [
        'timestamp' => now()->toIso8601String(),
        'steps' => []
    ];
    
    try {
        // Step 1: Test CouchDB connection
        $debug['steps'][] = 'Testing CouchDB connection...';
        $client = new CouchClient();
        $up = $client->up();
        $debug['steps'][] = ['couchdb_up' => $up];
        
        // Step 2: Test appointments database
        $debug['steps'][] = 'Testing appointments database...';
        $db = $client->db('appointments');
        $allDocs = $db->allDocs(['limit' => 1]);
        $debug['steps'][] = ['appointments_db' => [
            'total_rows' => $allDocs['total_rows'] ?? 'unknown',
            'has_docs' => isset($allDocs['rows'])
        ]];
        
        // Step 3: Check design document
        $debug['steps'][] = 'Checking design document...';
        $designDoc = $db->get('_design/appointments');
        $debug['steps'][] = ['design_doc' => [
            'exists' => !isset($designDoc['error']),
            'views' => array_keys($designDoc['views'] ?? []),
            'error' => $designDoc['error'] ?? null
        ]];
        
        // Step 4: Test view query
        $debug['steps'][] = 'Testing by_status view...';
        $viewResult = $db->view('appointments', 'by_status', [
            'key' => json_encode('scheduled'),
            'limit' => 5
        ]);
        $debug['steps'][] = ['view_query' => [
            'total_rows' => $viewResult['total_rows'] ?? 'unknown',
            'rows_count' => count($viewResult['rows'] ?? []),
            'error' => $viewResult['error'] ?? null
        ]];
        
        $debug['status'] = 'success';
        
    } catch (\Throwable $e) {
        $debug['status'] = 'failed';
        $debug['error'] = $e->getMessage();
        $debug['trace'] = $e->getTraceAsString();
    }
    
    return response()->json($debug);
});
