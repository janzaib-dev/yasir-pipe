<?php
// database/migrations/2025_08_18_000002_create_vendor_bilties_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorBiltiesTable extends Migration
{
    public function up()
    {
        Schema::create('vendor_bilties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('bilty_no')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('transporter_name')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_bilties');
    }
}
