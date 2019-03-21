<?php

namespace Trench\Models\Token;

use Trench\Models\AbstractModel;
use Trench\Models\TokenType\TokenType;
use Trench\Models\User\User;

class Token extends AbstractModel
{
    protected $fillable = [
        'id',
        'user_id',
        'token_type_id',
        'token',
        'expires_at'
    ];

    /** @var int $defaultTokenLength */
    public static $defaultTokenLength = 32;

    /**
     * @param array $attributes
     * @return AbstractModel
     */
    public static function create(array $attributes = []) : AbstractModel
    {
        if (!array_key_exists('token', $attributes)) {
            $attributes['token'] = str_random(static::$defaultTokenLength);
        }

        return parent::create($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tokenType()
    {
        return $this->belongsTo(TokenType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
