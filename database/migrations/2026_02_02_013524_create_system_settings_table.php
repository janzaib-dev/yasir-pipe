<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general'); // general, returns, sales, etc.
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('system_settings')->insert([
            [
                'key' => 'return_deadline_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'returns',
                'label' => 'Return Deadline (Days)',
                'description' => 'Number of days customers have to return items after purchase. Set to 0 to disable returns.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'return_require_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'returns',
                'label' => 'Require Manager Approval',
                'description' => 'If enabled, all returns must be approved by a manager before processing.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'return_auto_approve_threshold',
                'value' => '0',
                'type' => 'integer',
                'group' => 'returns',
                'label' => 'Auto-Approve Threshold',
                'description' => 'Returns under this amount will be auto-approved. Set to 0 to disable auto-approval.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
};
