<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make workflow_id nullable in the job_workflow table to support
 * on-the-go manual workflow executions that have no saved workflow record.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tablePrefix = getTablePrefix();
        $table = "{$tablePrefix}_job_workflow";
        $workflowsTable = "{$tablePrefix}_workflows";

        if (
            Schema::hasTable($table) &&
            Schema::hasTable($workflowsTable) &&
            Schema::hasColumn($table, 'workflow_id')
        ) {
            Schema::table($table, function (Blueprint $table) use ($tablePrefix) {
                // Drop the FK constraint first before modifying the column
                $table->dropForeign("{$tablePrefix}_job_workflow_workflow_id_foreign");

                // Make the column nullable so manual executions can use NULL
                $table->unsignedBigInteger('workflow_id')->nullable()->change();
            });

            // Set invalid workflow_id to NULL
            \DB::statement("UPDATE {$table} SET workflow_id = NULL WHERE workflow_id IS NOT NULL AND workflow_id NOT IN (SELECT id FROM {$workflowsTable})");

            // Re-add FK constraint (nullable foreign key is valid)
            Schema::table($table, function (Blueprint $table) use ($workflowsTable) {
                $table->foreign('workflow_id')->references('id')->on($workflowsTable)->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        $table = "{$tablePrefix}_job_workflow";
        $workflowsTable = "{$tablePrefix}_workflows";

        if (
            Schema::hasTable($table) &&
            Schema::hasTable($workflowsTable) &&
            Schema::hasColumn($table, 'workflow_id')
        ) {
            // Drop FK before modifying the column
            Schema::table($table, function (Blueprint $table) use ($tablePrefix) {
                $table->dropForeign("{$tablePrefix}_job_workflow_workflow_id_foreign");
            });

            \DB::statement("DELETE FROM {$table} WHERE workflow_id IS NULL");

            Schema::table($table, function (Blueprint $table) use ($workflowsTable) {
                // Make column NOT NULL
                $table->unsignedBigInteger('workflow_id')->nullable(false)->change();

                // Re-add FK constraint
                $table->foreign('workflow_id')->references('id')->on($workflowsTable)->onDelete('cascade');
            });
        }
    }
};
