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
        Schema::create('hr_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general'); // attendance, biometric, payroll, etc.
            $table->string('label')->nullable(); // Human-readable label
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default attendance settings
        DB::table('hr_settings')->insert([
            [
                'key' => 'attendance_punch_gap_minutes',
                'value' => '20',
                'type' => 'integer',
                'group' => 'attendance',
                'label' => 'Punch Gap (Minutes)',
                'description' => 'Minimum minutes between punches to be considered as separate check-in/check-out. Punches within this gap will be ignored as duplicates.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_settings');
    }
};
