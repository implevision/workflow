<?php

namespace Taurus\Workflow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Taurus\Workflow\Services\SES;

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
        $this->onQueue('workflow-post-action');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        switch ($this->payload['postAction']) {
            case '':
        }
    }
}
