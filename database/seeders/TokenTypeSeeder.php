<?php

use Trench\Models\TokenType\TokenType;

class TokenTypeSeeder extends AbstractArraySeeder
{
    protected $itemList = [
        ['name' => 'Login']
    ];

    public function __construct(TokenType $model)
    {
        $this->model = $model;
    }
}
