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
        if (! Schema::hasTable("{$tablePrefix}_workflow_logs")) {
            Schema::create("{$tablePrefix}_workflow_logs", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_workflow_id')->nullable();
                $table->unsignedBigInteger('workflow_id');
                $table->unsignedBigInteger('record_identifier')->nullable();
                $table->string('module');
                $table->enum('status', ['IN_PROGRESS', 'COMPLETED', 'ERROR'])
                    ->default('IN_PROGRESS');
                $table->string('action_type')->nullable();
                $table->string('action_track_id')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        Schema::dropIfExists("{$tablePrefix}_workflow_logs");
    }
};
