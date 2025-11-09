<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends BaseModel
{
    protected $table = 'patients';

    protected $fillable = [
        'full_name','birth_date','gender','id_number','phone','email',
        'address_street','address_ward','address_district','address_city','postal_code',
        'blood_type','allergies','chronic_conditions',
        'insurance_provider','insurance_policy_number','insurance_valid_until',
        'status'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'insurance_valid_until' => 'date',
        'allergies' => 'array',
        'chronic_conditions' => 'array',
    ];

    // Quan hệ
    public function medicalRecords(): HasMany { return $this->hasMany(MedicalRecord::class); }
    public function appointments(): HasMany   { return $this->hasMany(Appointment::class); }
    public function invoices(): HasMany       { return $this->hasMany(Invoice::class); }
    public function treatments(): HasMany     { return $this->hasMany(Treatment::class); }
    public function medicalTests(): HasMany   { return $this->hasMany(MedicalTest::class); }
}
