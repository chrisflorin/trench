<?php

namespace Trench\Models\Attribute;

use Trench\Models\AbstractModel;

class Attribute extends AbstractModel
{
    protected $fillable = [
        'name'
    ];

    public $timestamps = false;
}
