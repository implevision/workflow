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
        $tablePrefix = getTablePrefix();
        Schema::create("{$tablePrefix}_workflows", function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('module');
            $table->string('description')->nullable();
            $table->string('effective_action_to_execute_workflow')->comment('ON_RECORD_ACTION/ ON_DATE_TIME');
            $table->string('record_action_to_execute_workflow')->nullable()->comment('CREATE/ EDIT/ CREATE_OR_EDIT/ FIELD_UPDATE/ DELETE');
            $table->json('date_time_info_to_execute_workflow')->nullable();
            $table->string('workflow_execution_frequency')->nullable()->default('ONCE')->comment('ONCE / RECURRING');
            $table->timestamp('workflow_next_date_to_execute')->nullable();
            $table->text('aws_event_bridge_arn')->nullable()->comment('AWS Event Bridge ARN for scheduling the workflow');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        Schema::dropIfExists('{$tablePrefix}_workflows');
    }
};
