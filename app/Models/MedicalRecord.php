<?php

namespace App\Models;

class MedicalRecord extends BaseModel
{
    protected $table = 'medical_records';

    protected $fillable = [
        'patient_id','doctor_id','appointment_id',
        'visit_date','visit_type','chief_complaint',
        'vital_signs','physical_exam','diagnosis_primary_code',
        'diagnosis_primary_desc','diagnosis_primary_severity',
        'diagnosis_secondary','diagnosis_differential',
        'lifestyle_advice','follow_up_date','follow_up_notes',
        'attachments','status'
    ];

    protected $casts = [
        'visit_date' => 'datetime',
        'follow_up_date' => 'date',
        'vital_signs' => 'array',
        'physical_exam' => 'array',
        'diagnosis_secondary' => 'array',
        'diagnosis_differential' => 'array',
        'lifestyle_advice' => 'array',
        'attachments' => 'array',
    ];

    public function patient()     { return $this->belongsTo(Patient::class); }
    public function doctor()      { return $this->belongsTo(Doctor::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }

    public function treatments()  { return $this->hasMany(Treatment::class); }
    public function tests()       { return $this->hasMany(MedicalTest::class); }
    public function invoice()     { return $this->hasOne(Invoice::class); }
}
