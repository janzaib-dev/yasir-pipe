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
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Device name (e.g., "Main Office - BC-K40")
            $table->string('ip_address'); // Device IP address
            $table->integer('port')->default(4370); // Device port
            $table->string('username')->nullable(); // Admin username
            $table->string('password')->nullable(); // Encrypted admin password
            $table->string('model')->nullable(); // Device model (e.g., "BC-K40")
            $table->boolean('is_active')->default(true); // Enable/disable device
            $table->timestamp('last_sync_at')->nullable(); // Last successful sync timestamp
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_devices');
    }
};
