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
        Schema::create("{$tablePrefix}_workflow_config", function (Blueprint $table) use ($tablePrefix) {
            $table->id();
            $table->string('config_key');
            $table->string('config_value');
            $table->timestamp('last_checked')->nullable()->comment('Date when the workflow is scheduled to execute');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        Schema::dropIfExists("{$tablePrefix}_workflow_config");
    }
};
