<?php

namespace App\Models;

class Invoice extends BaseModel
{
    protected $table = 'invoices';

    protected $fillable = [
        'patient_id','medical_record_id',
        'invoice_number','invoice_date','due_date',
        'services','subtotal','tax_rate','tax_amount',
        'total_amount','insurance_coverage','insurance_amount',
        'patient_payment','payment_status','payment_method','paid_date'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'datetime',
        'services' => 'array',
        'subtotal' => 'integer',
        'tax_rate' => 'float',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'insurance_coverage' => 'float',
        'insurance_amount' => 'integer',
        'patient_payment' => 'integer',
    ];

    public function patient()       { return $this->belongsTo(Patient::class); }
    public function medicalRecord() { return $this->belongsTo(MedicalRecord::class); }
}
