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

        Schema::table($table, function (Blueprint $table) use ($tablePrefix) {
            // Drop the FK constraint first before modifying the column
            // $table->dropForeign("{$tablePrefix}_job_workflow_workflow_id_index");

            // Make the column nullable so manual executions can use NULL
            $table->unsignedBigInteger('workflow_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        $table = "{$tablePrefix}_job_workflow";

        Schema::table($table, function (Blueprint $table) use ($tablePrefix) {
            // Restore to NOT NULL (set any nulls to 0 first to avoid data issues)
            \DB::statement("UPDATE {$tablePrefix}_job_workflow SET workflow_id = 0 WHERE workflow_id IS NULL");
            $table->unsignedBigInteger('workflow_id')->nullable(false)->change();

            // Re-add FK constraint
            $table->foreign('workflow_id')->references('id')->on("{$tablePrefix}_workflows")->onDelete('cascade');
        });
    }
};
