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
        $tablePrefix = config('workflow.table_prefix', 'tbl_taurus');
        Schema::create("{$tablePrefix}_job_workflow", function (Blueprint $table) use ($tablePrefix) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->integer('batch_id')->notNullable();
            $table->enum('status', ['CREATED', 'IN_PROGRESS', 'COMPLETED', 'FAILED'])->default('CREATED');
            $table->mediumInteger('total_no_of_records_to_execute')->default(0);
            $table->mediumInteger('total_no_of_records_executed')->default(0);
            $table->json('response')->nullable();
            $table->timestamps();

            $table->index('workflow_id');
            $table->foreign('workflow_id')->references('id')->on("{$tablePrefix}_workflows")->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablePrefix = config('workflow.table_prefix', 'tbl_taurus');
        Schema::table("{$tablePrefix}_job_workflow", function ($table) use ($tablePrefix) {
            $table->dropForeign("{$tablePrefix}_job_workflow_workflow_id_foreign");
            $table->dropIndex("{$tablePrefix}_job_workflow_workflow_id_index");
        });
        Schema::dropIfExists("{$tablePrefix}_job_workflow");
    }
};
