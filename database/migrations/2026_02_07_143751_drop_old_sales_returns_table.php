<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old sales_returns table if it exists
        Schema::dropIfExists('sales_returns');
    }

    public function down(): void
    {
        // We won't recreate the old table in rollback
        // The new sale_returns table is the replacement
    }
};
