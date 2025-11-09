<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CouchDB\CouchClient;

// Interfaces
use App\Repositories\Interfaces\AppointmentsRepositoryInterface;
use App\Repositories\Interfaces\DoctorsRepositoryInterface;
use App\Repositories\Interfaces\InvoicesRepositoryInterface;
use App\Repositories\Interfaces\MedicalRecordsRepositoryInterface;
use App\Repositories\Interfaces\MedicalTestsRepositoryInterface;
use App\Repositories\Interfaces\MedicationsRepositoryInterface;
use App\Repositories\Interfaces\PatientsRepositoryInterface;
use App\Repositories\Interfaces\RolesRepositoryInterface;
use App\Repositories\Interfaces\StaffsRepositoryInterface;
use App\Repositories\Interfaces\TreatmentsRepositoryInterface;
use App\Repositories\Interfaces\UsersRepositoryInterface;

// CouchDB implementations (khớp đúng tên file bạn đang có)
use App\Repositories\CouchDB\AppointmentRepository;       // singular
use App\Repositories\CouchDB\DoctorsRepository;
use App\Repositories\CouchDB\InvoicesRepository;
use App\Repositories\CouchDB\MedicalRecordsRepository;
use App\Repositories\CouchDB\MedicalTestsRepository;
use App\Repositories\CouchDB\MedicationsRepository;
use App\Repositories\CouchDB\PatientRepository;           // singular
use App\Repositories\CouchDB\RolesRepository;
use App\Repositories\CouchDB\StaffsRepository;
use App\Repositories\CouchDB\TreatmentsRepository;
use App\Repositories\CouchDB\UsersRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // CouchDB HTTP client (singleton)
        $this->app->singleton(CouchClient::class, fn () => new CouchClient());

        // Interface -> Implementation (khớp EXACT với tên file hiện có)
        $this->app->bind(AppointmentsRepositoryInterface::class,     AppointmentRepository::class);
        $this->app->bind(DoctorsRepositoryInterface::class,          DoctorsRepository::class);
        $this->app->bind(InvoicesRepositoryInterface::class,         InvoicesRepository::class);
        $this->app->bind(MedicalRecordsRepositoryInterface::class,   MedicalRecordsRepository::class);
        $this->app->bind(MedicalTestsRepositoryInterface::class,     MedicalTestsRepository::class);
        $this->app->bind(MedicationsRepositoryInterface::class,      MedicationsRepository::class);
        $this->app->bind(PatientsRepositoryInterface::class,         PatientRepository::class);
        $this->app->bind(RolesRepositoryInterface::class,            RolesRepository::class);
        $this->app->bind(StaffsRepositoryInterface::class,           StaffsRepository::class);
        $this->app->bind(TreatmentsRepositoryInterface::class,       TreatmentsRepository::class);
        $this->app->bind(UsersRepositoryInterface::class,            UsersRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
