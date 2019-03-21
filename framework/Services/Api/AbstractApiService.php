<?php

namespace Trench\Services\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

abstract class AbstractApiService
{
    /** @var string $apiKey */
    protected $apiKey;

    /** @var string $apiKeyHeaderName */
    protected $apiKeyHeaderName = 'X-Api-Key';

    /** @var string $baseUri */
    protected $baseUri;

    /** @var \GuzzleHttp\Client $client */
    protected $client;

    /** @var \GuzzleHttp\Cookie\CookieJarInterface $cookieJar */
    protected $cookieJar;

    /** @var array $formParams */
    protected $formParams = [];

    /** @var array $headers */
    protected $headers = [];

    /** @var array $json */
    protected $json = [];

    /** @var array $query */
    protected $query = [];

    /** @var string $uri */
    protected $uri;

    /**
     * AbstractApiService constructor.
     * @param string $apiKeyEnvName
     */
    public function __construct($apiKeyEnvName = 'DEFAULT_API_KEY')
    {
        $this->apiKey(env($apiKeyEnvName));

        $this->reset();
    }

    /**
     * @param $apiKey
     * @return AbstractApiService
     */
    public function apiKey($apiKey) : AbstractApiService
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @param null $uri
     * @return AbstractApiService
     */
    public function baseUri($uri = null) : AbstractApiService
    {
        $this->baseUri = $uri;

        return $this;
    }

    /**
     * @param array $cookies
     * @return AbstractApiService
     */
    public function cookies($cookies = []) : AbstractApiService
    {
        foreach ($cookies as $cookie) {
            $cookie = new SetCookie($cookie);
            $this->cookieJar->setCookie($cookie);
        }

        return $this;
    }

    /**
     * @param null $uri
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function get($uri = null)
    {
        $uri = $this->baseUri . $uri;

        return $this->request('GET', $uri);
    }

    /**
     * @param array $headers
     * @return AbstractApiService
     */
    public function headers($headers = []) : AbstractApiService
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * @param $json
     * @return AbstractApiService
     */
    public function json($json) : AbstractApiService
    {
        $this->json = $json;

        return $this;
    }

    /**
     * @param $params
     * @return AbstractApiService
     */
    public function params($params) : AbstractApiService
    {
        $this->formParams = array_merge($this->formParams, $params);

        return $this;
    }

    /**
     * @param null $uri
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function post($uri = null)
    {
        return $this->request('POST', $uri);
    }

    /**
     * @param null $uri
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function put($uri = null)
    {
        return $this->request('PUT', $uri);
    }

    /**
     * @param array $query
     * @return AbstractApiService
     */
    public function query($query = []) : AbstractApiService
    {
        $this->query = array_merge($this->query, $query);

        return $this;
    }

    /**
     * @param $method
     * @param null $uri
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function request($method, $uri = null)
    {
        if (is_null($uri)) {
            $uri = $this->baseUri . $this->uri;
        }

        $guzzle = [
            'cookies' => $this->cookieJar,
            'headers' => array_merge([
                $this->apiKeyHeaderName => $this->apiKey
            ], $this->headers)
        ];

        if (!empty($this->formParams)) {
            $guzzle['form_params'] = $this->formParams;
        }

        if (!empty($this->json)) {
            $guzzle['json'] = $this->json;
        }

        if (!empty($this->query)) {
            $guzzle['query'] = $this->query;
        }

        return $this->client->request($method, $uri, $guzzle);
    }

    /**
     *
     */
    public function reset()
    {
        $this->client = new Client();
        $this->cookieJar =  new CookieJar();
    }

    /**
     * @param $uri
     * @return AbstractApiService
     */
    public function uri($uri) : AbstractApiService
    {
        $this->uri = $uri;

        return $this;
    }
}
