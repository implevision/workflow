<?php

namespace Taurus\Workflow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PostAction implements ShouldQueue
{
    use Queueable;

    private $payload;

    /**
     * Create a new job instance.
     */
    public function __construct($payload = [])
    {
        $this->payload = $payload;
        $queue = config('workflow.post_action_queue');
        $defaultQueue = getDefaultQueue();
        $this->onQueue($queue ?? $defaultQueue);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        setWorkflowDBConnection();
        switch ($this->payload['postAction']) {
            case '':
        }
    }
}
