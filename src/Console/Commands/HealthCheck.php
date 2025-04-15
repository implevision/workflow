<?php

namespace Taurus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Taurus\Workflow\Services\CheckUserCapabilities;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taurus:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Health Check';

    /**
     * The console command description.
     *
     * @var array
     */
    protected $keysToCheck = [
        'aws_profile',
        'aws_region',
        'table_prefix',
        'timezone',
        'aws_iam_role_arn_to_invoke_lambda_from_event_bridge',
        'aws_lambda_function_arn_to_invoke_workflow'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Processing, please wait...");
        $this->info(string: "Checking for configuration...");
        $this->checkForConfig();

        if (!$this->confirm('The configuration that is listed is correct?', true)) {
            $this->info('**** Please update the configuration and try again. **** ');
            return 1;
        }

        /*$this->info(string: "Checking for required config...");
        if (!$this->checkForEmptyConfig()) {
            return 1;
        }*/

        $this->info(string: "Checking for AWS permissions...");
        $this->checkForAwsPermission();

        /*$spinner = ['|', '/', '-', '\\'];
        $i = 0;

        $seconds = 1; // Simulate a 5 second task
        $endTime = time() + $seconds;

        while (time() < $endTime) {
            $this->output->write("\r" . $spinner[$i % count($spinner)] . " Loading...");
            usleep(100000); // 0.1 sec
            $i++;
        }

        $this->output->write("\r✔️  Done!           \n");*/
        return 0;
    }

    private function checkForConfig()
    {
        $headers = ['Key', 'Value'];
        $data = [];
        foreach ($this->keysToCheck as $key) {
            array_push($data, [$key, config("workflow.{$key}")]);
        }
        $this->table($headers, $data);
    }

    private function checkForEmptyConfig()
    {
        if (empty(config('workflow.aws_iam_role_arn_to_invoke_lambda_from_event_bridge'))) {
            $this->error('Please set the `aws_iam_role_arn_to_invoke_lambda_from_event_bridge` in the config/workflow.php file.');
            return 0;
        }

        if (empty(config('workflow.aws_lambda_function_arn_to_invoke_workflow'))) {
            $this->error('Please set the `aws_lambda_function_arn_to_invoke_workflow` in the config/workflow.php file.');
            return 0;
        }

        return 1;
    }

    private function checkForAwsPermission()
    {
        try {
            $allowedActions = checkUserCapabilities::check();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $headers = ['Permission', 'Allowed?', 'Comments'];
        $data = [];
        $permissions = config('workflow.required_actions');
        foreach ($allowedActions as $permission => $allowedAction) {
            $comment = array_key_exists($permission, $permissions) ? $permissions[$permission] : '';
            array_push($data, [$permission, $allowedAction, $comment]);
        }
        $this->table($headers, $data);
    }
}
