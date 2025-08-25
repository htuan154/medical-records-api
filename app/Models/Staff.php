<?php

namespace App\Models;

class Staff extends BaseModel
{
    protected $table = 'staffs';

    /**
     * Nếu bạn muốn ánh xạ sang document CouchDB (VD: "staff_2025_001"),
     * lưu _id CouchDB vào cột couch_id để liên kết 1-1 với users.linked_staff_id
     */
    protected $fillable = [
        'couch_id',          // _id của doc CouchDB (vd: "staff_2025_001")
        'full_name',
        'staff_type',        // nurse | receptionist | cashier | lab_technician | pharmacist | admin_staff
        'gender',
        'phone',
        'email',
        'department',
        'shift',             // JSON: { days:[], start:"", end:"" }
        'status',            // active | inactive
    ];

    protected $casts = [
        'shift'      => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const TYPES = [
        'nurse', 'receptionist', 'cashier', 'lab_technician', 'pharmacist', 'admin_staff'
    ];

    /* Scopes tiện dụng */
    public function scopeActive($q)   { return $q->where('status', 'active'); }
    public function scopeOfType($q, string $type) { return $q->where('staff_type', $type); }

    /* (Tùy chọn) Khai báo quan hệ Eloquent tới User theo linked_staff_id (nếu bạn giữ users trong SQL) */
    public function user()
    {
        // users.linked_staff_id trỏ tới staffs.couch_id (nếu bạn dùng CouchDB _id để liên kết)
        return $this->hasOne(User::class, 'linked_staff_id', 'couch_id');
    }

    /* Map -> CouchDB document */
    public function toCouchDoc(): array
    {
        return [
            '_id'        => $this->couch_id ?? null, // nếu null, CouchDB sẽ tự sinh khi tạo (khuyên đặt trước để liên kết cố định)
            'type'       => 'staff',
            'full_name'  => $this->full_name,
            'staff_type' => $this->staff_type,
            'gender'     => $this->gender,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'department' => $this->department,
            'shift'      => $this->shift,
            'status'     => $this->status,
        ];
    }

    /* Map <- CouchDB document (hỗ trợ sync về SQL nếu cần) */
    public static function fromCouchDoc(array $doc): self
    {
        $s = new self();
        $s->couch_id   = $doc['_id'] ?? null;
        $s->full_name  = $doc['full_name'] ?? null;
        $s->staff_type = $doc['staff_type'] ?? null;
        $s->gender     = $doc['gender'] ?? null;
        $s->phone      = $doc['phone'] ?? null;
        $s->email      = $doc['email'] ?? null;
        $s->department = $doc['department'] ?? null;
        $s->shift      = $doc['shift'] ?? null;
        $s->status     = $doc['status'] ?? 'active';
        return $s;
    }
}
