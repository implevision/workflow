<?php

namespace Taurus\Workflow\Repositories\Eloquent;

use Illuminate\Support\Facades\DB;
use Taurus\Workflow\Models\JobWorkflow;
use Taurus\Workflow\Validators\JobWorkflowValidator;
use Taurus\Workflow\Repositories\Contracts\JobWorkflowRepositoryInterface;

class JobWorkflowRepository implements JobWorkflowRepositoryInterface
{
    protected $model;

    public function __construct(JobWorkflow $model)
    {
        $this->model = $model;
    }

    // insert single
    public function createSingle(array $data): int
    {
        JobWorkflowValidator::validate($data);
        $jobWorkflowId = DB::transaction(function () use ($data) {
            $jobWorkflow = $this->model->create([
                'workflow_id' => $data['workflow_id'],
                'batch_id' => $this->model->getNextBatchId($data['workflow_id']),
                'status' => $data['status'] ?? $this->model::STATUS_CREATED,
                'total_no_of_records_to_execute' => $data['total_no_of_records_to_execute'] ?? 0,
                'total_no_of_records_executed' => $data['total_no_of_records_executed'] ?? 0,
                'response' => $data['response'] ?? [],
            ]);

            return $jobWorkflow->id;
        });
        return $jobWorkflowId;
    }

    // insert multiple
    public function createMultiple(array $records): void
    {
        DB::transaction(function () use ($records) {
            JobWorkflowValidator::validate($records);

            $now = now();
            $insertData = [];

            foreach ($records as $record) {
                $insertData[] = [
                    'workflow_id' => $record['workflow_id'],
                    'batch_id' => $this->model->getNextBatchId($record['workflow_id']),
                    'status' => $record['status'] ?? $this->model::STATUS_CREATED,
                    'total_no_of_records_to_execute' => $record['total_no_of_records_to_execute'] ?? 0,
                    'total_no_of_records_executed' => $record['total_no_of_records_executed'] ?? 0,
                    'response' => json_encode($record['response'] ?? []),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->model->insert($insertData);
        });
    }

    public function updateData(int $id, array $payload): void
    {
        DB::transaction(function () use ($id, $payload) {
            $workflow = $this->model->findOrFail($id);
            $workflow->update($payload);
        });
    }

    public function getInfo(int $id): array
    {
        $workflow = $this->model->findOrFail($id);
        return $workflow->toArray();
    }
}
