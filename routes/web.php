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
