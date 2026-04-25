<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change employee status enum from 'inactive' to 'non-active'
     */
    public function up(): void
    {
        // First, update any existing 'inactive' records to 'non-active'
        DB::statement("UPDATE hr_employees SET status = 'active' WHERE status = 'inactive'");
        
        // Modify the enum column to use 'non-active' instead of 'inactive'
        DB::statement("ALTER TABLE hr_employees MODIFY COLUMN status ENUM('active', 'non-active', 'terminated') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to 'inactive'
        DB::statement("UPDATE hr_employees SET status = 'active' WHERE status = 'non-active'");
        DB::statement("ALTER TABLE hr_employees MODIFY COLUMN status ENUM('active', 'inactive', 'terminated') DEFAULT 'active'");
    }
};
