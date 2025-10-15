<?php
use Illuminate\Support\Facades\Route;
use App\Services\CouchDB\CouchClient;

Route::get('/', fn() => 'OK');

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
