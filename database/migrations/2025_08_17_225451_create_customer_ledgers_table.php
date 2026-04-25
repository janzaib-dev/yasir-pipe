<?php

// database/migrations/2025_08_18_000003_create_customer_ledgers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerLedgersTable extends Migration
{
    public function up()
    {
        Schema::create('customer_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_or_user_id');
            $table->decimal('previous_balance', 12, 2)->nullable(0);
            $table->decimal('closing_balance', 12, 2)->nullable(0);
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_ledgers');
    }
}
