<?php

namespace Trench\Models\ApiKey;

use Trench\Models\AbstractModel;
use Trench\Models\System\System;

class ApiKey extends AbstractModel
{
    protected $fillable = [
        'name',
        'key'
    ];

    public static $defaultLength = 32;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function system()
    {
        return $this->hasOne(System::class);
    }

    /**
     * @param array $attributes
     * @return AbstractModel
     */
    public static function create(array $attributes = []) : AbstractModel
    {
        $attributes['key'] = str_random(static::$defaultLength);

        return parent::create($attributes);
    }
}
