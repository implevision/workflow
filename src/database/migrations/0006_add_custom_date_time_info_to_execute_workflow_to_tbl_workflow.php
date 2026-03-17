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

        if (! Schema::hasColumn("{$tablePrefix}_workflows", 'custom_date_time_info_to_execute_workflow')) {
            Schema::table("{$tablePrefix}_workflows", function (Blueprint $table) {
                $table->json('custom_date_time_info_to_execute_workflow')->nullable();
            });
        }

        if (Schema::hasColumn("{$tablePrefix}_workflows", 'effective_action_to_execute_workflow')) {
            Schema::table("{$tablePrefix}_workflows", function (Blueprint $table) {
                $table->string('effective_action_to_execute_workflow')
                    ->comment('ON_RECORD_ACTION/ ON_DATE_TIME/ CUSTOM_DATE_AND_TIME')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablePrefix = getTablePrefix();
        if (
            Schema::hasTable("{$tablePrefix}_workflows") &&
            Schema::hasColumn("{$tablePrefix}_workflows", 'custom_date_time_info_to_execute_workflow')
        ) {
            Schema::table("{$tablePrefix}_workflows", function (Blueprint $table) {
                $table->dropColumn('custom_date_time_info_to_execute_workflow');
            });
        }

        if (Schema::hasColumn("{$tablePrefix}_workflows", 'effective_action_to_execute_workflow')) {
            Schema::table("{$tablePrefix}_workflows", function (Blueprint $table) {
                $table->string('effective_action_to_execute_workflow')
                    ->comment('ON_RECORD_ACTION/ ON_DATE_TIME')
                    ->change();
            });
        }
    }
};
