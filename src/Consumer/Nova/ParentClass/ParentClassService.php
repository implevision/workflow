<?php

namespace Taurus\Workflow\Consumer\Nova\ParentClass;

class ParentClassService
{
    /**
     * Lets a consumer redirect a workflow onto a parent record, for entities
     * whose workflows are configured against the parent rather than the row
     * that changed.
     *
     * Nova has no such entities: every module is observed on the record its
     * workflows are configured against, so the entity is always used as-is.
     */
    public function getParentEntity($entityType, $entity)
    {
        return false;
    }
}
