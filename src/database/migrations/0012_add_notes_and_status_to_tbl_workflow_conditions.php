<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablePrefix = getTablePrefix();
        Schema::table("{$tablePrefix}_workflow_conditions", function (Blueprint $table) {
            $table->text('notes')->nullable()->after('conditions');
            $table->tinyInteger('status')->default(1)->after('notes');
        });
    }

    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        Schema::table("{$tablePrefix}_workflow_conditions", function (Blueprint $table) {
            $table->dropColumn(['notes', 'status']);
        });
    }
};
