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
        if (! Schema::hasTable('account_heads')) {
            Schema::create('account_heads', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('head_id')->nullable();
                $table->string('title');
                $table->string('account_code')->nullable();
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->string('type')->default('Debit'); // Debit or Credit
                $table->boolean('status')->default(1);
                $table->timestamps();

                $table->foreign('head_id')->references('id')->on('account_heads')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('account_heads');
    }
};
