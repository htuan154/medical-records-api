<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    /**
     * Giữ lại các field sẵn có (name/email/password/remember_token/...)
     * và bổ sung các trường để liên kết với CouchDB:
     *  - role_names:     mảng role ứng dụng (["nurse"], ["doctor"], ["clinic_admin"], ...)
     *  - account_type:   "patient" | "doctor" | "staff"
     *  - linked_*_id:    _id document CouchDB tương ứng
     *  - status:         active | inactive | locked
     *  - last_login:     datetime
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'password_hash',        // nếu bạn quản lý hash riêng (không cần nếu dùng cột password của Laravel)
        'role_names',           // JSON array
        'account_type',         // patient | doctor | staff
        'linked_patient_id',    // vd: patient_2024_001
        'linked_doctor_id',     // vd: doctor_2024_001
        'linked_staff_id',      // vd: staff_2025_001
        'status',               // active | inactive | locked
        'last_login',
    ];

    protected $hidden = [
        'password', 'password_hash', 'remember_token',
    ];

    protected $casts = [
        'role_names'        => 'array',
        'email_verified_at' => 'datetime',
        'last_login'        => 'datetime',
    ];

    /* Nếu bạn nhập password "thuần", tự hash luôn */
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /* (Tùy chọn) Liên kết tới Staff qua linked_staff_id (nếu Staff cũng ở SQL và có cột couch_id) */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'linked_staff_id', 'couch_id');
    }

    /* Map -> CouchDB user document (nếu bạn lưu users trong CouchDB) */
    public function toCouchDoc(): array
    {
        return [
            'type'           => 'user',
            'username'       => $this->username ?? $this->email,
            'email'          => $this->email,
            // chú ý: KHÔNG bao giờ đẩy password thuần lên CouchDB
            'password_hash'  => $this->password_hash ?? null,
            'role_names'     => $this->role_names ?? [],
            'account_type'   => $this->account_type,
            'linked_patient_id' => $this->linked_patient_id,
            'linked_doctor_id'  => $this->linked_doctor_id,
            'linked_staff_id'   => $this->linked_staff_id,
            'status'         => $this->status ?? 'active',
            'last_login'     => optional($this->last_login)->toIso8601String(),
        ];
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    // Helpers ngắn gọn
    public function hasRole(string $name): bool
    {
        return $this->roles->contains(fn($r) => $r->name === $name);
    }

    public function assignRole(string|array $names): void
    {
        $names = (array)$names;
        $roleIds = Role::whereIn('name', $names)->pluck('id')->all();
        $this->roles()->syncWithoutDetaching($roleIds);

        // Nếu bạn vẫn dùng users.role_names (JSON) để sync CouchDB, cập nhật luôn
        $current = collect($this->role_names ?? []);
        $this->role_names = $current->merge($names)->unique()->values()->all();
        $this->save();
    }
}
