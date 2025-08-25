<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staffs', function (Blueprint $table) {
            $table->id();
            $table->string('couch_id')->nullable()->unique(); // _id doc CouchDB để liên kết
            $table->string('full_name');
            $table->string('staff_type', 32); // nurse | receptionist | cashier | lab_technician | pharmacist | admin_staff
            $table->string('gender', 16)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('department')->nullable();
            $table->json('shift')->nullable(); // {days:[], start:"", end:""}
            $table->string('status', 16)->default('active'); // active | inactive
            $table->timestamps();
            $table->softDeletes();

            $table->index(['staff_type', 'status']);
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staffs');
    }
};
