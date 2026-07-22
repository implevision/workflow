<?php

namespace Taurus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Taurus\Workflow\Models\WorkflowLog;

class ExecuteWorkflowFromLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:execute-workflow-from-logs
                            {--workflowId=}
                            {--offset=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get workflow log records for a given workflow id';

    /**
     * Number of records processed per batch.
     *
     * @var int
     */
    protected const int BATCH_SIZE = 20;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $workflowId = $this->option('workflowId');
        $offset = (int) $this->option('offset', 0);

        if (! $workflowId) {
            $this->error('The --workflowId option is required.');

            return 1;
        }

        if ($offset === 0) {
            $total = WorkflowLog::where('workflow_id', $workflowId)->count();

            $this->info("Total log records for workflow id: $workflowId is $total");

            for ($nextOffset = self::BATCH_SIZE; $nextOffset < $total; $nextOffset += self::BATCH_SIZE) {
                $this->call('taurus:execute-workflow-from-logs', [
                    '--workflowId' => $workflowId,
                    '--offset' => $nextOffset,
                ]);
                sleep(10); // Sleep for 10 second to avoid overwhelming the system
            }
        }

        $this->info("Executing workflow id: $workflowId at offset: $offset");
        $logs = WorkflowLog::where('workflow_id', $workflowId)
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit(self::BATCH_SIZE)
            ->get();

        if ($logs->isEmpty()) {
            $this->info("No log records found for workflow id: $workflowId at offset: $offset");

            return 0;
        }

        foreach ($logs as $log) {
            $recordIdentifier = $log->record_identifier;

            $this->info("Dispatching workflow for record identifier: $recordIdentifier");

            if (config('app.env') == 'production') {
                $command = gitCommandToDispatchWorkflow($workflowId, $recordIdentifier);
                Artisan::call($command['command'], $command['options']);
            }
        }

        return 0;
    }
}
