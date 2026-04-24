<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablePrefix = getTablePrefix();
        $workflowTable = "{$tablePrefix}_workflows";

        if (Schema::hasTable($workflowTable)) {
            if (! Schema::hasColumn($workflowTable, 'field_to_observe')) {
                Schema::table($workflowTable, function (Blueprint $table) {
                    $table->string('field_to_observe')->nullable()->after('record_action_to_execute_workflow');
                });
            }
        }
    }

    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        $workflowTable = "{$tablePrefix}_workflows";

        if (Schema::hasTable($workflowTable)) {
            if (Schema::hasColumn($workflowTable, 'field_to_observe')) {
                Schema::table($workflowTable, function (Blueprint $table) {
                    $table->dropColumn('field_to_observe');
                });
            }
        }
    }
};
