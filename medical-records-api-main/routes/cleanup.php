<?php

use Illuminate\Support\Facades\Route;
use App\Services\CouchDB\CouchClient;

/**
 * Cleanup Routes - Xóa design documents bị lỗi
 */
Route::prefix('cleanup')->group(function () {
    
    /**
     * Xóa design document bị double prefix
     * GET /cleanup/fix-design-docs
     */
    Route::get('/fix-design-docs', function () {
        $results = [
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
            'operations' => []
        ];
        
        $databases = ['appointments', 'patients', 'doctors', 'staff', 'users', 'treatments', 'medical_records', 'invoices', 'medications'];
        
        foreach ($databases as $dbName) {
            try {
                $client = new CouchClient();
                $db = $client->db($dbName);
                
                // Kiểm tra design document sai
                $wrongId = "_design/_design/{$dbName}";
                $wrong = $db->get($wrongId);
                
                if (!isset($wrong['error'])) {
                    // Có design document sai, xóa nó
                    $deleteResult = $db->delete($wrongId, $wrong['_rev']);
                    $results['operations'][$dbName] = [
                        'deleted_wrong_doc' => $deleteResult,
                        'wrong_id' => $wrongId
                    ];
                } else {
                    $results['operations'][$dbName] = [
                        'message' => 'No wrong design document found'
                    ];
                }
                
            } catch (\Throwable $e) {
                $results['operations'][$dbName] = [
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return response()->json($results, 200);
    });
    
    /**
     * Xóa một design document cụ thể
     * GET /cleanup/delete-design/{db}/{designId}
     */
    Route::get('/delete-design/{db}/{designId}', function ($db, $designId) {
        try {
            $client = new CouchClient();
            $database = $client->db($db);
            
            // Decode designId nếu bị encode
            $designId = urldecode($designId);
            
            // Get current doc
            $doc = $database->get($designId);
            
            if (isset($doc['error'])) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => "Design document {$designId} not found in {$db}",
                    'error' => $doc
                ], 404);
            }
            
            // Delete
            $result = $database->delete($designId, $doc['_rev']);
            
            return response()->json([
                'status' => 'success',
                'database' => $db,
                'deleted_id' => $designId,
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
     * List tất cả design documents trong một database
     * GET /cleanup/list-designs/{db}
     */
    Route::get('/list-designs/{db}', function ($db) {
        try {
            $client = new CouchClient();
            $database = $client->db($db);
            
            $result = $database->allDocs([
                'startkey' => '"_design/"',
                'endkey' => '"_design0"',
                'include_docs' => true
            ]);
            
            $designs = [];
            if (isset($result['rows'])) {
                foreach ($result['rows'] as $row) {
                    $designs[] = [
                        'id' => $row['id'] ?? $row['key'] ?? null,
                        'rev' => $row['doc']['_rev'] ?? $row['value']['rev'] ?? null,
                        'has_views' => isset($row['doc']['views']),
                        'view_count' => isset($row['doc']['views']) ? count($row['doc']['views']) : 0
                    ];
                }
            }
            
            return response()->json([
                'status' => 'success',
                'database' => $db,
                'designs' => $designs,
                'count' => count($designs)
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    });
});
