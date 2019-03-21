<?php

namespace Trench\Repositories\Attribute;

use Trench\Models\Attribute\Attribute;
use Trench\Repositories\AbstractRepository;

class AttributeRepository extends AbstractRepository
{
    public function __construct(Attribute $model)
    {
        $this->model = $model;
    }
}
