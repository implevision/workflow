<?php

namespace Taurus\Workflow\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use SoftDeletes;

    protected $table;

    protected $fillable = [
        'module',
        'name',
        'description',
        'effective_action_to_execute_workflow',
        'record_action_to_execute_workflow',
        'date_time_info_to_execute_workflow',
        'custom_date_time_info_to_execute_workflow',
        'odyssey_action_to_execute_workflow',
        'workflow_execution_frequency',
        'workflow_next_date_to_execute',
        'is_active',
        'aws_event_bridge_arn',
    ];

    protected $casts = [
        'custom_date_time_info_to_execute_workflow' => 'json',
        'date_time_info_to_execute_workflow' => 'json',
        'workflow_next_date_to_execute' => 'datetime',
        'odyssey_action_to_execute_workflow' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = getTablePrefix();
        $this->table = $prefix.'_workflows';
    }

    public function conditions()
    {
        return $this->hasMany(WorkflowCondition::class, 'workflow_id');
    }

    public function calculateAndUpdateNextExecution(): string
    {
        $next = null;

        try {
            if (! empty($this->date_time_info_to_execute_workflow['recurringFrequency'] ?? null)) {
                $frequency = $this->date_time_info_to_execute_workflow['recurringFrequency'];
                $next = $this->getNextExecution($frequency);
            } elseif (! empty($this->date_time_info_to_execute_workflow['executionEffectiveDate'] ?? null)) {
                $next = $this->date_time_info_to_execute_workflow['executionEffectiveDate'];
            } elseif (
                ! empty($this->custom_date_time_info_to_execute_workflow['cronMinutes'] ?? null) &&
                ! empty($this->custom_date_time_info_to_execute_workflow['cronHours'] ?? null) &&
                ! empty($this->custom_date_time_info_to_execute_workflow['cronDayOfMonth'] ?? null) &&
                ! empty($this->custom_date_time_info_to_execute_workflow['cronMonth'] ?? null) &&
                ! empty($this->custom_date_time_info_to_execute_workflow['cronDayOfWeek'] ?? null) &&
                ! empty($this->custom_date_time_info_to_execute_workflow['cronYear'] ?? null)
            ) {
                // Build cron string from custom_date_time_info_to_execute_workflow
                $cron = sprintf(
                    '%s %s %s %s %s %s',
                    $this->custom_date_time_info_to_execute_workflow['cronMinutes'],
                    $this->custom_date_time_info_to_execute_workflow['cronHours'],
                    $this->custom_date_time_info_to_execute_workflow['cronDayOfMonth'],
                    $this->custom_date_time_info_to_execute_workflow['cronMonth'],
                    $this->custom_date_time_info_to_execute_workflow['cronDayOfWeek'],
                    $this->custom_date_time_info_to_execute_workflow['cronYear']
                );

                try {
                    $cronExp = \Cron\CronExpression::factory($cron);
                    $nextDate = $cronExp->getNextRunDate();
                    $next = $nextDate->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException('Invalid cron expression: '.$cron.' - '.$e->getMessage(), 0, $e);
                }
            }

            $this->update(['workflow_next_date_to_execute' => $next]);

            return $next;
        } catch (\Exception $e) {
            \Log::error('WORKFLOW: Error calculating next execution date: ', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
                'workflow_id' => $this->id,
                'cron_data' => $this->custom_date_time_info_to_execute_workflow,
            ]);

            return $next;
        }
    }

    public function getNextExecution(string $frequency): string
    {
        return match (strtoupper(trim($frequency))) {
            'ONCE' => Carbon::now()->modify('next day')->format('Y-m-d'),
            'MONTH' => Carbon::now()->modify('next Month')->startOfMonth()->format('Y-m-d'),
            'YEAR' => Carbon::now()->modify('first day of January next year')->format('Y-m-d'),
            'WEEK' => Carbon::now()->modify('next Monday')->format('Y-m-d '),
            default => throw new \InvalidArgumentException("Unknown schedule type: {$frequency}"),
        };
    }
}
