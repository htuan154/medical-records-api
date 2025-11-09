<?php

namespace App\Models;

class Appointment extends BaseModel
{
    protected $table = 'appointments';

    protected $fillable = [
        'patient_id','doctor_id',
        'scheduled_at','duration_minutes','type','priority',
        'reason','status','notes'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor()  { return $this->belongsTo(Doctor::class); }
}
