<?php

namespace App\Models;

class Role extends BaseModel
{
    protected $table = 'roles';

    protected $fillable = [
        'name',          // slug: clinic_admin, doctor, nurse, ...
        'display_name',  // nhãn hiển thị
        'permissions',   // JSON array: ["patient:read","patient:write",...]
        'status',        // active | inactive
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    // Users gán role qua pivot
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }

    // Map -> CouchDB doc (nếu sync sang CouchDB/roles)
    public function toCouchDoc(): array
    {
        return [
            'type'         => 'role',
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'permissions'  => $this->permissions ?? [],
            'status'       => $this->status ?? 'active',
        ];
    }

    public static function fromCouchDoc(array $doc): self
    {
        $r = new self();
        $r->name         = $doc['name'] ?? null;
        $r->display_name = $doc['display_name'] ?? $r->name;
        $r->permissions  = $doc['permissions'] ?? [];
        $r->status       = $doc['status'] ?? 'active';
        return $r;
    }
}
