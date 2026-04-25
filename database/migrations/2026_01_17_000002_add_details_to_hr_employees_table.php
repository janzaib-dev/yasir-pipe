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
            // $table->text('address')->nullable()->after('phone'); // Already exists, skip to avoid duplicate error
            // $table->boolean('is_docs_submitted')->default(false)->after('status'); // Already exists, skip to avoid duplicate error
            $table->string('document_degree')->nullable()->after('is_docs_submitted');
            $table->string('document_certificate')->nullable()->after('document_degree');
            $table->string('document_hsc_marksheet')->nullable()->after('document_certificate'); // Intermediate
            $table->string('document_ssc_marksheet')->nullable()->after('document_hsc_marksheet'); // 10th
            $table->string('document_cv')->nullable()->after('document_ssc_marksheet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            // $table->dropColumn('address'); // Do not drop, since we did not add in this migration
            // $table->dropColumn('is_docs_submitted'); // Do not drop, since we did not add in this migration
            $table->dropColumn([
                'document_degree',
                'document_certificate',
                'document_hsc_marksheet',
                'document_ssc_marksheet',
                'document_cv',
            ]);
        });
    }
};
