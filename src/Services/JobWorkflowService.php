<?php

namespace Taurus\Workflow\Services;

use Taurus\Workflow\Repositories\Contracts\JobWorkflowRepositoryInterface;

class JobWorkflowService extends WorkflowService
{
    private $jobWorkflowRepository;

    public function __construct(
        protected JobWorkflowRepositoryInterface $repository
    ) {
        $this->jobWorkflowRepository = $repository;
    }

    public function createSingle(array $record): void
    {
        $this->jobWorkflowRepository->createSingle($record);
    }

    public function createMultiple(array $records): void
    {
        $this->jobWorkflowRepository->createMultiple($records);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->jobWorkflowRepository->updateStatus($id, $status);
    }
}
