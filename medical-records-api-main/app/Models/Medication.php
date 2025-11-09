<?php

namespace App\Models;

class Medication extends BaseModel
{
    protected $table = 'medications';

    protected $fillable = [
        'name','generic_name','strength','dosage_form','manufacturer','barcode',
        'therapeutic_class','indications','contraindications','side_effects','drug_interactions',
        'current_stock','unit_cost','expiry_date','supplier','status'
    ];

    protected $casts = [
        'indications' => 'array',
        'contraindications' => 'array',
        'side_effects' => 'array',
        'drug_interactions' => 'array',
        'current_stock' => 'integer',
        'unit_cost' => 'integer',
        'expiry_date' => 'date',
    ];

    // N-N qua bảng pivot treatment_medication
    public function treatments()
    {
        return $this->belongsToMany(Treatment::class, 'treatment_medication')
            ->withPivot(['dosage','frequency','route','instructions','quantity_prescribed'])
            ->withTimestamps();
    }
}
