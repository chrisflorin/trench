<?php

namespace Trench\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Arrayable as IlluminateArrayable;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Pagination\LengthAwarePaginator as IlluminatePaginator;
use Illuminate\Routing\Controller as IlluminateController;
use Trench\Contexts\AbstractContext;
use Trench\Contexts\DefaultContext;
use Trench\Http\Requests\AbstractRequest;
use Trench\Services\Model\AbstractModelService;

abstract class AbstractApiController extends IlluminateController
{
    /** @var array $apiAllowedWith */
    protected $apiAllowedWith = [];

    /** @var AbstractContext $context */
    protected $context = null;
    protected $contexts = [
        'public' => DefaultContext::class
    ];

    /** @var array $data */
    protected $data = [];

    protected $defaultCount = 10;
    protected $defaultPage = 1;

    /** @var array $headers */
    protected $headers = [];

    protected $maxCount = 100;

    /** @var IlluminatePaginator $paginator */
    protected $paginator;

    /** @var AbstractModelService $service */
    protected $service;

    protected $sessionFilters = [];

    /** @var int $statusCode */
    protected $statusCode = IlluminateResponse::HTTP_OK;

    /** @var array $with */
    protected $with = [];

    protected function applySelect($method, AbstractRequest $request)
    {
        $select = $this->context->select($method);
        $this->service->select($select);
    }

    /**
     * @param $method 'index', 'show'
     * @param $request AbstractRequest
     */
    protected function applyWith($method, AbstractRequest $request)
    {
        if (array_key_exists($method, $this->with)) {
            //We trust the programmer of the Controller, so no need to sanitize
            $this->service->with($this->with[$method]);
        }

        $this->service->with($this->context->with($method));

        $with = $request->input('with', []);
        $sanitizedWith = $this->sanitizeWith($method, $request, $with);

        $this->service->with($sanitizedWith);
    }

    protected function authorizeStore(AbstractRequest $request) : bool
    {
        return true;
    }

    protected function chooseContext(AbstractRequest $request) : AbstractApiController
    {
        $contextType = $request->input('context', 'public');

        if (array_key_exists($contextType, $this->contexts)) {
            $context = app($this->contexts[$contextType]);
        } else {
            $context = app($this->contexts['public']);
        }

        $this->context = $context;

        return $this;
    }

    protected function data($data) : AbstractApiController
    {
        if ($data instanceof IlluminatePaginator) {
            $this->paginate($data);

            $data = $data->values()->toArray();
        } else if ($data instanceof IlluminateArrayable) {
            $data = [$data->toArray()];
        }

        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function destroy($id)
    {
        try {
            $result = \DB::transaction(function () use ($id) {
                return $this->service->destroy($id);
            });
        } catch (Exception $e) {
            throw $e;
        }

        return $this->status(IlluminateResponse::HTTP_NO_CONTENT)->respond();
    }

    protected function getFilter(AbstractRequest $request) : array
    {
        $filter = $request->input('filter', []);

        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }

        if ($request->hasSession()) {
            foreach ($this->sessionFilters as $sessionFilter) {
                $sessionValue = $request->session()->get($sessionFilter);
                if (!is_null($sessionValue)) {
                    $filter[$sessionFilter] = $sessionValue;
                }
            }
        }

        return $filter;
    }

    protected function getSorting(AbstractRequest $request) : array
    {
        $sorting = $request->input('sorting', []);

        if (is_string($sorting)) {
            $sorting = json_decode($sorting, true);
        }

        return $sorting;
    }

    /**
     * @param AbstractRequest $request
     * @return IlluminateResponse
     * @throws Exception
     */
    public function index(AbstractRequest $request) : IlluminateResponse
    {
        $this->chooseContext($request);

        $filter = $this->getFilter($request);
        $sorting = $this->getSorting($request);

        $count = $request->input('count');

        if (is_null($request->input('all'))) {
            $count = min($count ? : $this->defaultCount, $this->maxCount);
            $page = $request->input('page') ? : $this->defaultPage;

            $this->service->paginate($count, $page);
        } else {
            if (!is_null($count)) {
                $this->service->limit($count);
            } else {
                $this->service->limit($this->maxCount);
            }
        }

        $this->applySelect('index', $request);
        $this->applyWith('index', $request);

        try {
            $result = $this->service->index($filter, $sorting);
        } catch (Exception $e) {
            throw $e;
        }

        return $this->data($result)->respond();
    }

    protected function paginate(IlluminatePaginator $paginator)
    {
        $this->paginator = $paginator;

        return $this;
    }

    /**
     * @param array $headers
     * @return mixed
     */
    protected function respond($headers = [])
    {
        $headers = array_merge($this->headers, $headers);

        if (!is_null($this->paginator)) {
            $data = $this->paginator->toArray();
        } else {
            $data = [
                'data' => $this->data
            ];
        }

        $data['data'] = $this->context->scrub($data['data']);

        return \Response::make($this->data, $this->statusCode, $headers);
    }

    /**
     * @param $method
     * @param AbstractRequest $request
     * @param array $with
     * @return array
     */
    protected function sanitizeWith($method, AbstractRequest $request, array $with = []) : array
    {
        $result = [];
        if (!array_key_exists($method, $this->apiAllowedWith)) {
            return $result;
        }

        $allowedWith = $this->apiAllowedWith[$method];

        foreach ($with as $relation) {
            if (in_array($relation, $allowedWith)) {
                $result[] = $relation;
            }
        }

        return $result;
    }

    /**
     * @param $id
     * @param AbstractRequest $request
     * @return IlluminateResponse
     * @throws Exception
     */
    public function show($id, AbstractRequest $request) : IlluminateResponse
    {
        $this->chooseContext($request);

        $this->applyWith('show', $request);

        try {
            $result = $this->service->find($id);
        } catch (Exception $e) {
            throw $e;
        }

        return $this->data($result)->respond();
    }

    /**
     * @param $statusCode
     * @return $this
     */
    protected function status($statusCode) : self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @param AbstractRequest $request
     * @return IlluminateResponse
     * @throws Exception
     */
    public function store(AbstractRequest $request) : IlluminateResponse
    {
        //TODO: Authorization should be added to the validation layer
        if (!$this->authorizeStore($request)) {
            return $this->status(IlluminateResponse::HTTP_UNAUTHORIZED)->respond();
        }

        $this->chooseContext($request);

        try {
            $result = \DB::transaction(function () use ($request) {
                $input = $request->all();

                return $this->service->create($input);
            });

            $this->data($result);
        } catch (Exception $e) {
            //TODO: Handle and return general exceptions
            throw $e;
        }

        if (is_null($result)) {
            $this->status(IlluminateResponse::HTTP_BAD_REQUEST);
        } else {
            $this->status(IlluminateResponse::HTTP_CREATED);
        }

        return $this->respond();
    }
    /**
     * @param $id
     * @param AbstractRequest $request
     * @return IlluminateResponse
     * @throws Exception
     */
    public function update($id, AbstractRequest $request) : IlluminateResponse
    {
        try {
            $result = \DB::transaction(function () use ($id, $request) {
                $input = $request->all();

                return $this->service->update($id, $input);
            });
        } catch (Exception $e) {
            //TODO: Handle and return general exceptions
            throw $e;
        }

        return $this->status(IlluminateResponse::HTTP_RESET_CONTENT)->respond();
    }
}
