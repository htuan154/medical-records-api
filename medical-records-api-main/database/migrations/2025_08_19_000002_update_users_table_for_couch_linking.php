<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('users', 'password_hash')) {
                $table->string('password_hash')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'role_names')) {
                $table->json('role_names')->nullable()->after('password_hash');
            }
            if (!Schema::hasColumn('users', 'account_type')) {
                $table->string('account_type', 16)->nullable()->after('role_names'); // patient | doctor | staff
            }
            if (!Schema::hasColumn('users', 'linked_patient_id')) {
                $table->string('linked_patient_id')->nullable()->after('account_type');
            }
            if (!Schema::hasColumn('users', 'linked_doctor_id')) {
                $table->string('linked_doctor_id')->nullable()->after('linked_patient_id');
            }
            if (!Schema::hasColumn('users', 'linked_staff_id')) {
                $table->string('linked_staff_id')->nullable()->after('linked_doctor_id'); // _id CouchDB của staff
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status', 16)->default('active')->after('linked_staff_id'); // active | inactive | locked
            }
            if (!Schema::hasColumn('users', 'last_login')) {
                $table->timestamp('last_login')->nullable()->after('status');
            }

            $table->index(['account_type', 'status']);
            $table->index('linked_staff_id');
            $table->index('linked_patient_id');
            $table->index('linked_doctor_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            
        });
    }
};
