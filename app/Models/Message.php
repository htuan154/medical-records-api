<?php

namespace App\Models;

class Message extends BaseModel
{
    protected $table = 'messages';

    protected $fillable = [
        'consultation_id',
        'sender_id',
        'sender_type',
        'sender_name',
        'message',
        'is_read',
        'created_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
