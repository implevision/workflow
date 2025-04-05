<?php

namespace Taurus\Workflow\Observers;

class ModelObserver
{

    public $model;

    public function __construct($model)
    {
        $this->model = $model;
    }
}
