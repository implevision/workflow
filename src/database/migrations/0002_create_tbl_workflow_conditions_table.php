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
        Schema::create("{$tablePrefix}_workflow_conditions", function (Blueprint $table) use ($tablePrefix) {
            $table->id();
            $table->json('conditions')->nullable();
            $table->unsignedBigInteger('workflow_id');
            $table->timestamps();
            $table->softDeletes(); // deleted_at

            $table->index('workflow_id');
            $table->foreign('workflow_id')->references('id')->on("{$tablePrefix}_workflows")->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        Schema::table("{$tablePrefix}_workflow_conditions", function ($table) use ($tablePrefix) {
            $table->dropForeign("{$tablePrefix}_workflow_conditions_workflow_id_foreign");
            $table->dropIndex("{$tablePrefix}_workflow_conditions_workflow_id_index");
        });
        Schema::dropIfExists("{$tablePrefix}_workflow_conditions");
    }
};
