<?php

namespace App\Models;

class Treatment extends BaseModel
{
    protected $table = 'treatments';

    protected $fillable = [
        'patient_id','doctor_id','medical_record_id',
        'treatment_name','start_date','end_date','duration_days','treatment_type',
        'monitor_parameters','monitor_frequency','monitor_next_check','status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'duration_days' => 'integer',
        'monitor_parameters' => 'array',
        'monitor_next_check' => 'datetime',
    ];

    public function patient()       { return $this->belongsTo(Patient::class); }
    public function doctor()        { return $this->belongsTo(Doctor::class); }
    public function medicalRecord() { return $this->belongsTo(MedicalRecord::class); }

    public function medications()
    {
        return $this->belongsToMany(Medication::class, 'treatment_medication')
            ->withPivot(['dosage','frequency','route','instructions','quantity_prescribed'])
            ->withTimestamps();
    }
}
