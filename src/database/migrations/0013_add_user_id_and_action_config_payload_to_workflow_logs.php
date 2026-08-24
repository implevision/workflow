<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablePrefix = config('workflow.table_prefix', 'tb_taurus');
        $table = "{$tablePrefix}_workflow_logs";

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'user_id')) {
                $blueprint->unsignedInteger('user_id')->nullable()->after('action_track_id');
            }
            if (! Schema::hasColumn($table, 'action_config_payload')) {
                $blueprint->json('action_config_payload')->nullable()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        $tablePrefix = config('workflow.table_prefix', 'tb_taurus');
        $table = "{$tablePrefix}_workflow_logs";

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (Schema::hasColumn($table, 'action_config_payload')) {
                $blueprint->dropColumn('action_config_payload');
            }
            if (Schema::hasColumn($table, 'user_id')) {
                $blueprint->dropColumn('user_id');
            }
        });
    }
};
