<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablePrefix = getTablePrefix();
        $jobWorkflowTable = "{$tablePrefix}_job_workflow";

        if (! Schema::hasTable($jobWorkflowTable) || Schema::hasColumn($jobWorkflowTable, 'reference_id')) {
            \Log::error("WORKFLOW: Table $jobWorkflowTable does not exist or already has column 'reference_id'. Migration cannot be applied.");

            return;
        }

        Schema::table($jobWorkflowTable, function (Blueprint $table) {
            $table->string('reference_id')->nullable()->after('response');
        });
    }

    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        $jobWorkflowTable = "{$tablePrefix}_job_workflow";

        if (! Schema::hasTable($jobWorkflowTable) || ! Schema::hasColumn($jobWorkflowTable, 'reference_id')) {
            \Log::error("WORKFLOW: Table $jobWorkflowTable does not exist or does not have column 'reference_id'. Migration cannot be reverted.");

            return;
        }

        Schema::table($jobWorkflowTable, function (Blueprint $table) {
            $table->dropColumn('reference_id');
        });
    }
};
