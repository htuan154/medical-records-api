<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CouchDB\CouchClient;
use App\Services\CouchDB\AppointmentService;
use App\Services\CouchDB\PatientService;
use App\Services\CouchDB\DoctorService;
use App\Services\CouchDB\StaffService;
use App\Services\CouchDB\UserService;
use App\Services\CouchDB\TreatmentService;
use App\Services\CouchDB\MedicalRecordService;
use App\Services\CouchDB\InvoiceService;
use App\Services\CouchDB\MedicationService;

class CouchDBSetupCommand extends Command
{
    protected $signature = 'couchdb:setup';
    protected $description = 'Setup CouchDB databases and design documents';

    public function handle()
    {
        $this->info('🚀 Starting CouchDB setup...');
        
        // Step 1: Create databases
        $this->info('📦 Creating databases...');
        $databases = [
            'appointments', 'patients', 'doctors', 'staffs', 'users',
            'treatments', 'medical_records', 'invoices', 'medications'
        ];
        
        $couchClient = app(CouchClient::class);
        
        foreach ($databases as $dbName) {
            try {
                $response = $couchClient->pendingRequest()->put("/{$dbName}");
                
                if ($response->successful() || $response->status() === 412) {
                    $this->info("  ✅ Created database: $dbName");
                } else {
                    $this->error("  ❌ Failed to create database: $dbName (status {$response->status()})");
                }
            } catch (\Throwable $e) {
                $this->error("  ❌ Error creating database $dbName: " . $e->getMessage());
            }
        }
        
        // Step 2: Create design documents
        $this->info('📝 Creating design documents...');
        
        $services = [
            'appointments' => AppointmentService::class,
            'patients' => PatientService::class,
            'doctors' => DoctorService::class,
            'staff' => StaffService::class,
            'users' => UserService::class,
            'treatments' => TreatmentService::class,
            'medical_records' => MedicalRecordService::class,
            'invoices' => InvoiceService::class,
            'medications' => MedicationService::class,
        ];
        
        foreach ($services as $name => $serviceClass) {
            try {
                $service = app($serviceClass);
                $result = $service->ensureDesignDoc();
                
                if (isset($result['ok']) && $result['ok']) {
                    $this->info("  ✅ Setup design doc for: $name");
                } elseif (isset($result['error'])) {
                    $this->error("  ❌ Failed to setup $name: " . $result['error']);
                } else {
                    $this->warning("  ⚠️  Unexpected result for $name");
                }
            } catch (\Throwable $e) {
                $this->error("  ❌ Error setting up $name: " . $e->getMessage());
            }
        }
        
        $this->info('✅ CouchDB setup completed!');
        return 0;
    }
}
