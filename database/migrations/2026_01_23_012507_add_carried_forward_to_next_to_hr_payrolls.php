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
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->decimal('carried_forward_to_next', 10, 2)->default(0)->after('carried_forward_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->dropColumn('carried_forward_to_next');
        });
    }
};
