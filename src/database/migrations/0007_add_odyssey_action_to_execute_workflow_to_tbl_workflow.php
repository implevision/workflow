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
        $workflowTable = "{$tablePrefix}_workflows";

        if (Schema::hasTable($workflowTable)) {
            if (! Schema::hasColumn($workflowTable, 'odyssey_action_to_execute_workflow')) {
                Schema::table($workflowTable, function (Blueprint $table) {
                    $table->json('odyssey_action_to_execute_workflow')->nullable();
                });
            }

            if (Schema::hasColumn($workflowTable, 'effective_action_to_execute_workflow')) {
                Schema::table($workflowTable, function (Blueprint $table) {
                    $table->string('effective_action_to_execute_workflow')
                        ->comment('ON_RECORD_ACTION/ ON_DATE_TIME/ CUSTOM_DATE_AND_TIME/ ODYSSEY_ACTION')
                        ->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        $workflowTable = "{$tablePrefix}_workflows";

        if (Schema::hasTable($workflowTable)) {
            if (Schema::hasColumn($workflowTable, 'odyssey_action_to_execute_workflow')) {
                Schema::table($workflowTable, function (Blueprint $table) {
                    $table->dropColumn('odyssey_action_to_execute_workflow');
                });
            }

            if (Schema::hasColumn($workflowTable, 'effective_action_to_execute_workflow')) {
                Schema::table($workflowTable, function (Blueprint $table) {
                    $table->string('effective_action_to_execute_workflow')
                        ->comment('ON_RECORD_ACTION/ ON_DATE_TIME/ CUSTOM_DATE_AND_TIME')
                        ->change();
                });
            }
        }
    }
};
