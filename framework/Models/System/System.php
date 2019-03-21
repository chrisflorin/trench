<?php

namespace Trench\Models\System;

use Trench\Models\AbstractModel;
use Trench\Models\User\User;

class System extends AbstractModel
{
    protected $fillable = [
        'name',
        'api_domain'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'system_users');
    }
}