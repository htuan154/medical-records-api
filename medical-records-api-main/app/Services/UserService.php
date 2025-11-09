<?php

namespace App\Services;

use App\Repositories\Interfaces\UsersRepositoryInterface;
use App\Repositories\Interfaces\PatientsRepositoryInterface;
use App\Repositories\Interfaces\DoctorsRepositoryInterface;
use App\Repositories\Interfaces\StaffsRepositoryInterface;

/**
 * User Service
 * 
 * Handles user-related business logic including linking with patients, doctors, and staff
 */
class UserService
{
    public function __construct(
        private UsersRepositoryInterface $usersRepo,
        private PatientsRepositoryInterface $patientRepo,
        private DoctorsRepositoryInterface $doctorRepo,
        private StaffsRepositoryInterface $staffRepo
    ) {}

    /**
     * Get list of patients that are not yet linked to any user account
     *
     * @param int $limit Number of patients to return
     * @param int $skip Number of patients to skip
     * @return array
     */
    public function getAvailablePatients(int $limit = 50, int $skip = 0): array
    {
        try {
            // Get all users to identify linked patients
            $allUsers = $this->usersRepo->allFull(1000, 0);
            $linkedPatientIds = [];
            
            if (isset($allUsers['rows'])) {
                foreach ($allUsers['rows'] as $row) {
                    $user = $row['doc'] ?? null;
                    if ($user && !empty($user['linked_patient_id'])) {
                        $linkedPatientIds[] = $user['linked_patient_id'];
                    }
                }
            }

            // Get all patients
            $allPatients = $this->patientRepo->allFull(1000, 0);
            $availablePatients = [];
            
            if (isset($allPatients['rows'])) {
                foreach ($allPatients['rows'] as $row) {
                    $patient = $row['doc'] ?? null;
                    if ($patient && !in_array($patient['_id'], $linkedPatientIds)) {
                        $availablePatients[] = $patient;
                    }
                }
            }

            // Apply pagination to available patients
            $paginatedPatients = array_slice($availablePatients, $skip, $limit);

            return [
                'total_rows' => count($availablePatients),
                'offset' => $skip,
                'rows' => array_map(function($patient) {
                    return ['doc' => $patient];
                }, $paginatedPatients)
            ];

        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to get available patients: ' . $e->getMessage());
        }
    }

    /**
     * Get list of doctors that are not yet linked to any user account
     *
     * @param int $limit Number of doctors to return
     * @param int $skip Number of doctors to skip
     * @return array
     */
    public function getAvailableDoctors(int $limit = 50, int $skip = 0): array
    {
        try {
            // Get all users to identify linked doctors
            $allUsers = $this->usersRepo->allFull(1000, 0);
            $linkedDoctorIds = [];
            
            if (isset($allUsers['rows'])) {
                foreach ($allUsers['rows'] as $row) {
                    $user = $row['doc'] ?? null;
                    if ($user && !empty($user['linked_doctor_id'])) {
                        $linkedDoctorIds[] = $user['linked_doctor_id'];
                    }
                }
            }

            // Get all doctors
            $allDoctors = $this->doctorRepo->allFull(1000, 0);
            $availableDoctors = [];
            
            if (isset($allDoctors['rows'])) {
                foreach ($allDoctors['rows'] as $row) {
                    $doctor = $row['doc'] ?? null;
                    if ($doctor && !in_array($doctor['_id'], $linkedDoctorIds)) {
                        $availableDoctors[] = $doctor;
                    }
                }
            }

            // Apply pagination to available doctors
            $paginatedDoctors = array_slice($availableDoctors, $skip, $limit);

            return [
                'total_rows' => count($availableDoctors),
                'offset' => $skip,
                'rows' => array_map(function($doctor) {
                    return ['doc' => $doctor];
                }, $paginatedDoctors)
            ];

        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to get available doctors: ' . $e->getMessage());
        }
    }

    /**
     * Get list of staff that are not yet linked to any user account
     *
     * @param int $limit Number of staff to return
     * @param int $skip Number of staff to skip
     * @return array
     */
    public function getAvailableStaffs(int $limit = 50, int $skip = 0): array
    {
        try {
            // Get all users to identify linked staff
            $allUsers = $this->usersRepo->allFull(1000, 0);
            $linkedStaffIds = [];
            
            if (isset($allUsers['rows'])) {
                foreach ($allUsers['rows'] as $row) {
                    $user = $row['doc'] ?? null;
                    if ($user && !empty($user['linked_staff_id'])) {
                        $linkedStaffIds[] = $user['linked_staff_id'];
                    }
                }
            }

            // Get all staff
            $allStaffs = $this->staffRepo->allFull(1000, 0);
            $availableStaffs = [];
            
            if (isset($allStaffs['rows'])) {
                foreach ($allStaffs['rows'] as $row) {
                    $staff = $row['doc'] ?? null;
                    if ($staff && !in_array($staff['_id'], $linkedStaffIds)) {
                        $availableStaffs[] = $staff;
                    }
                }
            }

            // Apply pagination to available staff
            $paginatedStaffs = array_slice($availableStaffs, $skip, $limit);

            return [
                'total_rows' => count($availableStaffs),
                'offset' => $skip,
                'rows' => array_map(function($staff) {
                    return ['doc' => $staff];
                }, $paginatedStaffs)
            ];

        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to get available staff: ' . $e->getMessage());
        }
    }
}