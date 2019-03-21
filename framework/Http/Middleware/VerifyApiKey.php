<?php

namespace Trench\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as IlluminateRepository;
use Trench\Exceptions\Middleware\InvalidApiKeyException;
use Trench\Models\ApiKey\ApiKey;

class VerifyApiKey extends AbstractMiddleware
{
    /** @var ApiKey $apiKey */
    protected $apiKeyModel;

    /**
     * VerifyApiKey constructor.
     * @param IlluminateRepository $config
     * @param ApiKey $apiKey
     */
    public function __construct(IlluminateRepository $config, ApiKey $apiKey)
    {
        parent::__construct($config);

        $this->apiKeyModel = $apiKey;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param null $guard
     * @return mixed
     * @throws InvalidApiKeyException
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $apiKeyHeader = $request->headers->get('X-Api-Key');

        $apiKey = $this->apiKeyModel->findFirstBy('key', $apiKeyHeader);

        if (is_null($apiKey)) {
            throw new InvalidApiKeyException();
        }

        $system = $apiKey->system;

        if (!is_null($system)) {
            $request->merge([
                'system_id' => $system->id
            ]);
//        } else {
            //TODO: Error out if a system is undefined
        }

        return $next($request);
    }
}