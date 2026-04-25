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
        // Add columns to employees table
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('designation_id')
                  ->constrained('hr_shifts')->nullOnDelete();
            $table->time('custom_start_time')->nullable()->after('shift_id');
            $table->time('custom_end_time')->nullable()->after('custom_start_time');
            $table->text('face_encoding')->nullable()->after('custom_end_time'); // JSON face descriptor
            $table->string('face_photo')->nullable()->after('face_encoding'); // Reference photo path
        });

        // Add columns to attendances table
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->time('check_in_time')->nullable()->after('date');
            $table->time('check_out_time')->nullable()->after('check_in_time');
            $table->string('check_in_photo')->nullable()->after('check_out_time');
            $table->string('check_out_photo')->nullable()->after('check_in_photo');
            $table->boolean('is_late')->default(false)->after('status');
            $table->integer('late_minutes')->default(0)->after('is_late');
            $table->boolean('is_early_leave')->default(false)->after('late_minutes');
            $table->integer('early_leave_minutes')->default(0)->after('is_early_leave');
            $table->decimal('total_hours', 5, 2)->nullable()->after('early_leave_minutes');
            $table->text('notes')->nullable()->after('total_hours');
            $table->string('device_id')->nullable()->after('notes'); // For future device integration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['shift_id', 'custom_start_time', 'custom_end_time', 'face_encoding', 'face_photo']);
        });

        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_time', 'check_out_time', 'check_in_photo', 'check_out_photo',
                'is_late', 'late_minutes', 'is_early_leave', 'early_leave_minutes',
                'total_hours', 'notes', 'device_id'
            ]);
        });
    }
};
