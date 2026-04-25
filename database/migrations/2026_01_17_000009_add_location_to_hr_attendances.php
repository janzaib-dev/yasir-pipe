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
        Schema::table('hr_attendances', function (Blueprint $table) {
            // Check-in location
            $table->decimal('check_in_latitude', 10, 8)->nullable()->after('check_in_photo');
            $table->decimal('check_in_longitude', 11, 8)->nullable()->after('check_in_latitude');
            $table->string('check_in_location')->nullable()->after('check_in_longitude');
            
            // Check-out location
            $table->decimal('check_out_latitude', 10, 8)->nullable()->after('check_out_photo');
            $table->decimal('check_out_longitude', 11, 8)->nullable()->after('check_out_latitude');
            $table->string('check_out_location')->nullable()->after('check_out_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_latitude',
                'check_in_longitude', 
                'check_in_location',
                'check_out_latitude',
                'check_out_longitude',
                'check_out_location',
            ]);
        });
    }
};
