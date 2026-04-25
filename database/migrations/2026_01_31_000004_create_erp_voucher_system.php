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
        // 1. UPDATE Account Heads (Hierarchy & Types)
        Schema::table('account_heads', function (Blueprint $table) {
            if (! Schema::hasColumn('account_heads', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
                $table->foreign('parent_id')->references('id')->on('account_heads')->onDelete('cascade');
            }
            if (! Schema::hasColumn('account_heads', 'level')) {
                $table->tinyInteger('level')->default(1)->comment('1=Group, 2=Control, 3=Detail')->after('name');
            }
            if (! Schema::hasColumn('account_heads', 'code')) {
                $table->string('code')->nullable()->after('id');
            }
            if (! Schema::hasColumn('account_heads', 'type')) {
                // ENUM for Account Types
                $table->enum('type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'])->nullable()->after('name');
            }
        });

        // 2. UPDATE Accounts (Real-time Balance)
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'current_balance')) {
                $table->decimal('current_balance', 15, 2)->default(0)->after('opening_balance');
            }
            if (! Schema::hasColumn('accounts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });

        // 3. CREATE Voucher Masters (Header)
        if (! Schema::hasTable('voucher_masters')) {
            Schema::create('voucher_masters', function (Blueprint $table) {
                $table->id();

                // ENUMS
                $table->enum('voucher_type', ['receipt', 'payment', 'expense', 'journal', 'contra'])->index();
                $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft')->index();

                $table->string('voucher_no')->unique(); // RV-2026-0001
                $table->date('date');
                $table->string('fiscal_year')->nullable(); // 2026-2027

                // Polymorphic Party (Customer, Vendor, Employee, or just Account)
                $table->nullableMorphs('party'); // party_type, party_id

                $table->text('remarks')->nullable();
                $table->decimal('total_amount', 15, 2)->default(0);

                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('posted_at')->nullable();

                $table->timestamps();
            });
        }

        // 4. CREATE Voucher Details (Line Items)
        if (! Schema::hasTable('voucher_details')) {
            Schema::create('voucher_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('voucher_master_id')->constrained('voucher_masters')->onDelete('cascade');

                $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');

                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);

                $table->string('narration')->nullable();
                $table->timestamps();
            });
        }

        // 5. CREATE Journal Entries (The General Ledger)
        if (! Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();

                // Source Link (e.g. VoucherMaster id:1, or Sale id:50)
                $table->nullableMorphs('source'); // source_type, source_id

                $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');

                $table->date('entry_date')->index();
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);

                $table->string('description')->nullable();
                $table->boolean('is_reconciled')->default(false);
                $table->timestamp('reconciled_at')->nullable();

                $table->timestamps();

                // Indexes for Reporting
                $table->index(['account_id', 'entry_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('voucher_details');
        Schema::dropIfExists('voucher_masters');

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['current_balance', 'is_active']);
        });

        Schema::table('account_heads', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'level', 'code', 'type']);
        });
    }
};
