<?php

namespace Taurus\Workflow\Repositories\Contracts;

interface JobWorkflowRepositoryInterface
{
    public function createSingle(array $data): int;
    public function createMultiple(array $records): void;
    public function updateData(int $id, array $payload): void;
    public function getInfo(int $id): array;
}
