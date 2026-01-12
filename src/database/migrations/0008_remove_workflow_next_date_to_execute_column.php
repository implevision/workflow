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
            if (Schema::hasColumn($workflowTable, 'workflow_next_date_to_execute')) {
                Schema::table($workflowTable, function (Blueprint $table) {
                    $table->dropColumn('workflow_next_date_to_execute');
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
            if (!Schema::hasColumn($workflowTable, 'workflow_next_date_to_execute')) {
                Schema::table($workflowTable, function (Blueprint $table) {
                    $table->timestamp('workflow_next_date_to_execute')->nullable();
                });
            }
        }
    }
};
