<?php

namespace App\Models;

class MedicalTest extends BaseModel
{
    protected $table = 'medical_tests';

    protected $fillable = [
        'patient_id','doctor_id','medical_record_id',
        'test_type','test_name',
        'ordered_at','sample_collected_at','result_at',
        'results','interpretation','status','lab_technician'
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'sample_collected_at' => 'datetime',
        'result_at' => 'datetime',
        'results' => 'array',
    ];

    public function patient()       { return $this->belongsTo(Patient::class); }
    public function doctor()        { return $this->belongsTo(Doctor::class); }
    public function medicalRecord() { return $this->belongsTo(MedicalRecord::class); }
}
