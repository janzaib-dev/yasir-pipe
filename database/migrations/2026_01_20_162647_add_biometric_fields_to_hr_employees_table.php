<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->foreignId('biometric_device_id')->nullable()->after('face_photo')->constrained('biometric_devices')->onDelete('set null');
            $table->string('device_user_id')->nullable()->after('biometric_device_id'); // Employee ID on the device
            $table->timestamp('fingerprint_enrolled_at')->nullable()->after('device_user_id');
            $table->timestamp('last_device_sync_at')->nullable()->after('fingerprint_enrolled_at');
            
            $table->index('device_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropForeign(['biometric_device_id']);
            $table->dropColumn(['biometric_device_id', 'device_user_id', 'fingerprint_enrolled_at', 'last_device_sync_at']);
        });
    }
};
