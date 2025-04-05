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
        Schema::create("{$tablePrefix}_workflow_conditions", function (Blueprint $table) {
            $table->id();
            $table->json('conditions')->nullable();
            $table->unsignedBigInteger('workflow_id');
            $table->timestamps();
            $table->softDeletes(); // deleted_at

            $table->index('workflow_id');
            $table->foreign('workflow_id')->references('id')->on('tbl_workflows')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_workflow_conditions', function ($table) {
            $table->dropForeign('tbl_workflow_conditions_workflow_id_foreign');
            $table->dropIndex('tbl_workflow_conditions_workflow_id_index');
        });
        Schema::dropIfExists('tbl_workflow_conditions');
    }
};
