<?php

namespace Taurus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Taurus\Workflow\Services\DispatchManualWorkflowService;

class DispatchManualWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:dispatch-manual-workflow
                            {--module=}
                            {--recordIdentifier=}
                            {--selectedActions=}
                            {--actionsConfig=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a manual workflow execution for a specific record';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $module = $this->option('module');
        $recordIdentifier = $this->option('recordIdentifier') ?? 0;

        $selectedActions = $this->option('selectedActions');
        $selectedActions = $selectedActions ? json_decode($selectedActions, true) : [];

        $actionsConfig = $this->option('actionsConfig');
        $actionsConfig = $actionsConfig ? json_decode($actionsConfig, true) : [];

        if (! $module) {
            $this->error('The --module option is required.');

            return;
        }

        if (empty($selectedActions)) {
            $this->error('The --selectedActions option is required and must not be empty.');

            return;
        }

        $this->info("Dispatching manual workflow for module: $module, record: $recordIdentifier");

        try {
            \Log::info('MANUAL WORKFLOW - Dispatching via artisan command', [
                'module' => $module,
                'recordIdentifier' => $recordIdentifier,
                'selectedActions' => $selectedActions,
            ]);

            $service = new DispatchManualWorkflowService(
                $module,
                $recordIdentifier,
                $selectedActions,
                $actionsConfig
            );

            $service->dispatch();
        } catch (\Exception $e) {
            $errorMessage = "MANUAL WORKFLOW - Error dispatching for module $module, record $recordIdentifier: ".$e->getMessage();
            \Log::error($errorMessage);
            $this->error($errorMessage);
        }
    }
}
