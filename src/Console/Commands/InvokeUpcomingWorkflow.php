<?php

namespace Taurus\Workflow\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Taurus\Workflow\Models\Workflow;
use Taurus\Workflow\Services\CheckUserCapabilities;
use Taurus\Workflow\Services\WorkflowService;

class InvokeUpcomingWorkflow extends Command
{
    protected $workflowService;
    public function __construct(WorkflowService $workflowService,)
    {
        $this->workflowService = $workflowService;
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:invoke-upcoming-workflow {--self-test=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invoke the upcoming workflow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $selfTest = $this->option('self-test');
        if ($selfTest) {
            \Log::info("Self test mode enabled. No workflows will be dispatched.");
            $this->selfTest();
        } else {
            $this->info("Executing workflow...");
            $workflows = $this->workflowService->getWorkflowsExecutingToday();
            if (empty($workflows)) {
                $this->info("No workflows to execute today.");
                return 0;
            }

            $this->workflowService->scheduleWorkflows($workflows);
        }

        return 0;
    }

    private function selfTest()
    {
        $testDir = base_path('vendor/taurus/workflow/tests/invoke-upcoming-workflow');
        $testJsonFiles = collect(File::files($testDir))
            ->filter(fn($file) => $file->getExtension() === 'json');

        $testCases = $this->getTestDataWithParams($testJsonFiles, []);

        $expectedDates = [
            'ONCE'  => now()->addDay()->toISOString(),
            'MONTH' => now()->addMonth()->startOfMonth()->toISOString(),
            'YEAR'  => now()->addYear()->startOfYear()->toISOString(),
        ];

        $workflow = new Workflow();
        $actualDates = [];

        foreach ($testCases as $testCase) {
            $executionDate = Carbon::parse(
                $testCase['when']['dateTimeInfoToExecuteWorkflow']['executionEffectiveDate']
            );

            $frequency = $testCase['when']['dateTimeInfoToExecuteWorkflow']['recurringFrequency'];

            $actualDateObj = $workflow->getNextExecution($executionDate, $frequency);
            $expectedDateObj = Carbon::parse($expectedDates[$frequency]);

            $actualDates[] = [
                'frequency' => $frequency,
                'expected'  => $expectedDateObj->toDateString(),
                'actual'    => $actualDateObj->toDateString(),
                'is_match'  => $expectedDateObj->isSameDay($actualDateObj),
            ];
        }

        $this->table(['Frequency', 'Expected', 'Actual', 'Match'], $actualDates);
    }

    private function getTestDataWithParams(Collection $testFiles, array $params): array
    {
        $testCases = [];

        foreach ($testFiles as $file) {
            $jsonContent = File::get($file->getPathname());

            // Replace {{param}} with the corresponding value
            foreach ($params as $key => $value) {
                $jsonContent = str_replace('{{' . $key . '}}', $value, $jsonContent);
            }

            // Replace {{now}} with current timestamp
            $jsonContent = str_replace('{{now}}', date('H:i'), $jsonContent);
            $jsonContent = str_replace('{{today}}', date('Y-m-d'), $jsonContent);

            $testCases[] = json_decode($jsonContent, true);
        }

        return $testCases;
    }
}
