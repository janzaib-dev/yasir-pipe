<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update 'sales' table
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'invoice_no')) {
                    $table->string('invoice_no')->nullable()->unique();
                }
                if (!Schema::hasColumn('sales', 'manual_invoice')) {
                    $table->string('manual_invoice')->nullable();
                }
                if (!Schema::hasColumn('sales', 'party_type')) {
                    $table->string('party_type')->nullable();
                }
                if (!Schema::hasColumn('sales', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable();
                }
                if (!Schema::hasColumn('sales', 'sub_customer')) {
                    $table->string('sub_customer')->nullable();
                }
                if (!Schema::hasColumn('sales', 'filer_type')) {
                    $table->string('filer_type')->nullable();
                }
                if (!Schema::hasColumn('sales', 'address')) {
                    $table->text('address')->nullable();
                }
                if (!Schema::hasColumn('sales', 'tel')) {
                    $table->string('tel')->nullable();
                }
                if (!Schema::hasColumn('sales', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
                if (!Schema::hasColumn('sales', 'quantity')) {
                    $table->text('quantity')->nullable();
                }

                // Financials
                if (!Schema::hasColumn('sales', 'sub_total1')) {
                    $table->decimal('sub_total1', 12, 2)->default(0);
                }
                if (!Schema::hasColumn('sales', 'sub_total2')) {
                    $table->decimal('sub_total2', 12, 2)->default(0);
                }
                if (!Schema::hasColumn('sales', 'discount_percent')) {
                    $table->decimal('discount_percent', 5, 2)->default(0);
                }
                if (!Schema::hasColumn('sales', 'discount_amount')) {
                    $table->decimal('discount_amount', 12, 2)->default(0);
                }
                if (!Schema::hasColumn('sales', 'previous_balance')) {
                    $table->decimal('previous_balance', 12, 2)->default(0);
                }
                if (!Schema::hasColumn('sales', 'total_balance')) {
                    $table->decimal('total_balance', 12, 2)->default(0);
                }
                if (!Schema::hasColumn('sales', 'receipt1')) {
                    $table->decimal('receipt1', 12, 2)->default(0);
                }
                if (!Schema::hasColumn('sales', 'receipt2')) {
                    $table->decimal('receipt2', 12, 2)->default(0);
                }
                if (!Schema::hasColumn('sales', 'final_balance1')) {
                    $table->decimal('final_balance1', 12, 2)->default(0);
                }
                if (!Schema::hasColumn('sales', 'final_balance2')) {
                    $table->decimal('final_balance2', 12, 2)->default(0);
                }
                // total_net already exists in original migration but check anyway
                 if (!Schema::hasColumn('sales', 'total_net')) {
                    $table->decimal('total_net', 12, 2)->nullable();
                }
            });
        } else {
            // Create sales table if it doesn't exist (unlikely given context)
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_no')->unique();
                $table->string('manual_invoice')->nullable();
                $table->string('party_type')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('sub_customer')->nullable();
                $table->string('filer_type')->nullable();
                $table->text('address')->nullable();
                $table->string('tel')->nullable();
                $table->text('remarks')->nullable();
                $table->text('quantity')->nullable();

                $table->decimal('sub_total1', 12, 2)->default(0);
                $table->decimal('sub_total2', 12, 2)->default(0);
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('previous_balance', 12, 2)->default(0);
                $table->decimal('total_balance', 12, 2)->default(0);
                $table->decimal('receipt1', 12, 2)->default(0);
                $table->decimal('receipt2', 12, 2)->default(0);
                $table->decimal('final_balance1', 12, 2)->default(0);
                $table->decimal('final_balance2', 12, 2)->default(0);
                $table->decimal('total_net')->nullable();

                $table->timestamps();
            });
        }

        // 2. Create 'sale_items' table
        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sale_id'); 
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->decimal('stock', 12, 2)->default(0);
                $table->decimal('price_level', 12, 2)->default(0);
                $table->decimal('sales_price', 12, 2)->default(0);
                $table->decimal('sales_qty', 12, 2)->default(0);
                $table->decimal('retail_price', 12, 2)->default(0);
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);

                $table->timestamps();

                // Foreign key (assuming sales table exists now)
                $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        // We typically don't remove columns from sales in down() to avoid data loss
    }
};
