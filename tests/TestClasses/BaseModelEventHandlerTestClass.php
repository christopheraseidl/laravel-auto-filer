<?php

namespace christopheraseidl\ModelFiler\Tests\TestClasses;

use christopheraseidl\ModelFiler\Handlers\BaseModelEventHandler;
use Illuminate\Database\Eloquent\Model;

class BaseModelEventHandlerTestClass extends BaseModelEventHandler
{
    protected function createJobsFromAttribute(Model $model, string $attribute, ?string $type = null): ?array
    {
        return $attribute == 'string'
            ? ['job1', 'job2']
            : null;
    }

    protected function getBatchDescription(): string
    {
        return 'Test description';
    }
}
