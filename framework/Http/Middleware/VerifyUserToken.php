<?php

namespace Trench\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Auth;
use Trench\Exceptions\Middleware\InvalidUserTokenException;
use Trench\Models\Token\Token;
use Trench\Models\User\User;

class VerifyUserToken extends AbstractMiddleware
{
    /** @var Token $tokenModel */
    protected $tokenModel;

    /** @var User $userModel */
    protected $userModel;

    /**
     * VerifyUserToken constructor.
     * @param Repository $config
     * @param Token $tokenModel
     * @param User $userModel
     */
    public function __construct(Repository $config, Token $tokenModel, User $userModel)
    {
        parent::__construct($config);

        $this->tokenModel = $tokenModel;
        $this->userModel = $userModel;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param null $guard
     * @return mixed
     * @throws InvalidUserTokenException
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $tokenValue = $request->headers->get('X-Api-User-Token');

        $token = $this->tokenModel->findFirstBy('token', $tokenValue);

        if (is_null($token)) {
            throw new InvalidUserTokenException();
        }

        //TODO: Check token expiration

        $user = $token->user;

        Auth::login($user);

        $request->merge([
            'user_id' => $user->id
        ]);

        return $next($request);
    }
}
