<?php

namespace Taurus\Workflow\Tests\Support;

use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;

class FakeJobWorkflowRepository extends JobWorkflowRepository
{
    public array $created = [];

    public ?\Throwable $failure = null;

    public function __construct() {}

    public function createSingle(array $data): int
    {
        if ($this->failure) {
            throw $this->failure;
        }

        $this->created[] = $data;

        return count($this->created);
    }
}
