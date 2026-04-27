<?php

namespace Taurus\Workflow\Consumer\Taurus\ParentClass;

class ParentClassService
{
    public function getParentEntity($entityType, $entity)
    {
        match ($entityType) {
            'Avatar\Infrastructure\Models\Api\v1\TbPoappsmaster' => $this->resolveParentEntity($entity),
            default => false,
        };
    }

    private function resolveParentEntity($entity)
    {
        $appMasterInfo = \Avatar\Infrastructure\Models\Api\v1\TbPoappsmaster::where('id', $entity)->first();

        if ($appMasterInfo) {
            return [
                'entity' => $appMasterInfo->n_POTransactionFK,
                'entityType' => 'Avatar\Infrastructure\Models\Api\v1\TbPotransaction',
            ];
        }

        return false;
    }
}
