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
        Schema::table('journal_entries', function (Blueprint $table) {
            // Polymorphic Party (Customer, Vendor, etc.)
            // Allows us to filter General Ledger entries for a specific party
            if (! Schema::hasColumn('journal_entries', 'party_type')) {
                $table->nullableMorphs('party'); // party_type, party_id
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropMorphs('party');
        });
    }
};
