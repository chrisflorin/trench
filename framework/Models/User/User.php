<?php namespace Trench\Models\User;

use Illuminate\Auth\Authenticatable as IlluminateAuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable as IlluminateAuthenticatable;
use Illuminate\Support\Facades\Hash as IlluminateHash;
use Trench\Models\AbstractModel;

class User extends AbstractModel implements IlluminateAuthenticatable
{
    use IlluminateAuthenticatableTrait;

    protected $attributesTable = 'user_attributes';

    protected $fillable = [
        'name',
        'email',
        'password'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @param array $attributes
     * @return AbstractModel
     */
    public static function create(array $attributes = []) : AbstractModel
    {
        if (array_key_exists('password', $attributes)) {
            $attributes['password'] = IlluminateHash::make($attributes['password']);
        }

        return parent::create($attributes);
    }
}
