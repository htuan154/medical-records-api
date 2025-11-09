<?php

use Illuminate\Support\Facades\Route;
use App\Services\CouchDB\RoleService;

Route::get('/debug/roles', function () {
    try {
        $roleService = app(RoleService::class);
        $result = $roleService->list(50, 0, []);
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'count' => count($result['rows'] ?? [])
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/debug/couchdb-roles-raw', function () {
    try {
        $client = app(\App\Services\CouchDB\CouchClient::class);
        $result = $client->db('roles')->allDocs(['include_docs' => true]);
        
        // Extract documents and show their structure
        $docs = [];
        if (isset($result['rows'])) {
            foreach ($result['rows'] as $row) {
                if (isset($row['doc'])) {
                    $docs[] = [
                        'id' => $row['doc']['_id'] ?? 'no-id',
                        'type' => $row['doc']['type'] ?? 'no-type',
                        'name' => $row['doc']['name'] ?? 'no-name',
                        'display_name' => $row['doc']['display_name'] ?? 'no-display'
                    ];
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'raw_data' => $result,
            'extracted_docs' => $docs,
            'total_raw' => count($result['rows'] ?? []),
            'total_docs' => count($docs)
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});