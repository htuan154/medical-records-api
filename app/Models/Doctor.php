<?php

namespace App\Models;

class Doctor extends BaseModel
{
    protected $table = 'doctors';

    protected $fillable = [
        'full_name','birth_date','gender','phone','email',
        'license_number','specialty','sub_specialties',
        'experience_years','education','certifications',
        'working_days','working_hours_start','working_hours_end',
        'break_start','break_end','status'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'sub_specialties' => 'array',
        'education' => 'array',
        'certifications' => 'array',
        'working_days' => 'array',
    ];

    public function medicalRecords() { return $this->hasMany(MedicalRecord::class); }
    public function appointments()   { return $this->hasMany(Appointment::class); }
    public function treatments()     { return $this->hasMany(Treatment::class); }
    public function medicalTests()   { return $this->hasMany(MedicalTest::class); }
}
