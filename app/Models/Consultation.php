<?php

namespace App\Models;

class Consultation extends BaseModel
{
    protected $table = 'consultations';

    protected $fillable = [
        'patient_id',
        'patient_info',
        'staff_id',
        'staff_info',
        'status',
        'last_message',
        'last_message_at',
        'unread_count_patient',
        'unread_count_staff',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'patient_info' => 'array',
        'staff_info' => 'array',
        'unread_count_patient' => 'integer',
        'unread_count_staff' => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
