<?php

namespace Taurus\Workflow\Repositories\Eloquent;

use Taurus\Workflow\Models\Workflow;
use Illuminate\Database\Eloquent\Collection;
use Taurus\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    protected $model;

    public function __construct(Workflow $model)
    {
        $this->model = $model;
    }

    public function all(): ?Collection
    {
        return $this->model->all(['id', 'module', 'name', 'description']);
    }

    public function create(array $data): Workflow
    {
        return $this->model->create($data);
    }

    public function getById(int $id, bool $withDeleted = false): Workflow
    {
        $query = $this->model->with([
            'conditions' => function ($query) use ($withDeleted) {
                if ($withDeleted) {
                    $query->withTrashed();
                }
            },
            'conditions.actions' => function ($query) use ($withDeleted) {
                if ($withDeleted) {
                    $query->withTrashed();
                }
            }
        ]);

        if ($withDeleted) {
            $query = $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    public function update(int $id, array $data): ?Workflow
    {
        $workflow = $this->model->findOrFail($id);
        $workflow->update($data);
        return $workflow;
    }

    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    public function restore(int $id): bool
    {
        return $this->model->where('id', $id)->restore() > 0;
    }
}
