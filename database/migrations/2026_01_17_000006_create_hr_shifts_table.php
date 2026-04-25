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
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Morning Shift", "Evening Shift"
            $table->time('start_time'); // e.g., "09:00:00"
            $table->time('end_time'); // e.g., "18:00:00"
            $table->time('break_start')->nullable(); // Optional break
            $table->time('break_end')->nullable();
            $table->integer('grace_minutes')->default(15); // Late grace period
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_shifts');
    }
};
