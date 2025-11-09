<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

try {
    $couchdb = $app->make(\App\Services\CouchDB\CouchClient::class);
    
    $modules = [
        'medical-records' => 'Medical Records',
        'doctors' => 'Doctors',
        'staffs' => 'Staff',
        'patients' => 'Patients', 
        'treatments' => 'Treatments',
        'medications' => 'Medications',
        'medical_tests' => 'Medical Tests',
        'invoices' => 'Invoices'
    ];
    
    echo "=== CURRENT DATA COUNT ===\n";
    foreach ($modules as $collection => $name) {
        try {
            $result = $couchdb->db($collection)->allDocs();
            $count = isset($result['rows']) ? count($result['rows']) : 0;
            echo sprintf("%-20s: %d\n", $name, $count);
        } catch (Exception $e) {
            echo sprintf("%-20s: Error - %s\n", $name, $e->getMessage());
        }
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}