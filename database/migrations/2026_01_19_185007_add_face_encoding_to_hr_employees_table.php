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
            if (!Schema::hasColumn('hr_employees', 'face_encoding')) {
                $table->text('face_encoding')->nullable()->after('status');
            }
            if (!Schema::hasColumn('hr_employees', 'face_photo')) {
                $table->string('face_photo')->nullable()->after('face_encoding');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn(['face_encoding', 'face_photo']);
        });
    }
};
