<?php namespace Trench\Models\TokenType;

use Trench\Models\AbstractModel;

class TokenType extends AbstractModel
{
    const LOGIN_ID = 1;

    protected $fillable = [
        'name'
    ];

    public $timestamps = false;
}
