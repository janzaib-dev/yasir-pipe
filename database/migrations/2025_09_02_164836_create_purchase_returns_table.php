<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();

            // Main references
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('return_invoice')->unique();
            $table->date('return_date'); // ✅ return date
            $table->text('return_reason')->nullable(); // ✅ return reason

            // Transport Info
            $table->string('transport')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('delivery_person')->nullable();

            // Warehouse
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('set null');

            // Financials
            $table->decimal('bill_amount', 12, 2)->default(0);
            $table->decimal('item_discount', 12, 2)->default(0);
            $table->decimal('extra_discount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};

