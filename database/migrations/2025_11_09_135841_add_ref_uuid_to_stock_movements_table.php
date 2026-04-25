<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'ref_uuid')) {
                $table->string('ref_uuid')->nullable()->after('ref_id')->index();
            }
        });
    }
    public function down(): void {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'ref_uuid')) {
                $table->dropColumn('ref_uuid');
            }
        });
    }
};

