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
        $tablePrefix = config('workflow.table_prefix', 'tb_taurus');
        Schema::create("{$tablePrefix}_workflow_actions", function (Blueprint $table) use ($tablePrefix) {
            $table->id();
            $table->unsignedBigInteger('condition_id');
            $table->json('payload');
            $table->timestamps();
            $table->softDeletes(); // deleted_at

            $table->index('condition_id');
            $table->foreign('condition_id')->references('id')->on("{$tablePrefix}_workflow_conditions")->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablePrefix = config('workflow.table_prefix', 'tb_taurus');
        Schema::table("{$tablePrefix}_workflow_actions", function ($table) use ($tablePrefix) {
            $table->dropForeign("{$tablePrefix}_workflow_actions_condition_id_foreign");
            $table->dropIndex("{$tablePrefix}_workflow_actions_condition_id_index");
        });
        Schema::dropIfExists("{$tablePrefix}_workflow_actions");
    }
};
