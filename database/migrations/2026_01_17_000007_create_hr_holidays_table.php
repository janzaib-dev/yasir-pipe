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
        if (!Schema::hasTable('hr_holidays')) {
            Schema::create('hr_holidays', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g., "Eid ul Fitr", "Independence Day"
                $table->date('date');
                $table->enum('type', ['public', 'company', 'optional'])->default('public');
                $table->text('description')->nullable();
                $table->timestamps();
                
                $table->unique('date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_holidays');
    }
};
