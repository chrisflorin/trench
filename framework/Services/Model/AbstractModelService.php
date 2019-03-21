<?php

namespace Trench\Services\Model;

use Trench\Models\AbstractModel;
use Trench\Repositories\AbstractRepository;
use Trench\Repositories\Attribute\AttributeRepository;

abstract class AbstractModelService
{
    protected $afterCreate = [];

    /** @var AbstractRepository $repo */
    protected $repo;

    /**
     * @param $attributes
     * @return AbstractModel
     */
    public function create($attributes)
    {
        $result = $this->repo->create($attributes);

        if (array_key_exists('attributes', $attributes)) {
            $this->eavAttributes($result, $attributes['attributes']);
        }

        //After the model has been created, run any functions that should be called after
        foreach ($this->afterCreate as $functionName) {
            $this->$functionName($attributes, $result);
        }

        return $result;
    }

    /**
     * @param $id
     * @return int
     */
    public function destroy($id)
    {
        return $this->repo->destroy($id);
    }

    /**
     * @param AbstractModel $model
     * @param $attributes
     */
    protected function eavAttributes(AbstractModel $model, $attributes)
    {
        //If there is not an attributes table, ignore
        if (is_null($model->getAttributesTable())) {
            return;
        }

        //Attributes may come in as an array or json string
        if (is_string($attributes)) {
            $attributes = json_decode($attributes, true);
        }

        //If there are no attributes to change, there is nothing to do
        if (empty($attributes)) {
            return;
        }

        $attributeRepository = app(AttributeRepository::class);

        //Add old values to resync with
        $oldValues = $model->attributes->keyBy('id')->toArray();
        $syncTo = [];
        foreach ($oldValues as $oldAttribute) {
            $syncTo[$oldAttribute['id']] = [
                'value' => $oldAttribute['pivot']['value']
            ];
        }

        //Set new attribute values
        foreach ($attributes as $attributeName => $attributeValue) {
            $attribute = $attributeRepository->firstOrCreate([
                'name' => $attributeName
            ]);

            $syncTo[$attribute->id] = [
                'value' => $attributeValue
            ];
        }

        //Sync
        $model->attributes()->sync($syncTo);
    }

    /**
     * @param $filters
     * @return $this
     */
    public function filterBy($filters)
    {
        $this->repo->filterBy($filters);

        return $this;
    }

    /**
     * @param $id
     * @return AbstractModel
     */
    public function find($id)
    {
        return $this->repo->find($id);
    }

    /**
     * @param $key
     * @param $value
     * @param string $operator
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     */
    public function findFirstBy($key, $value, $operator = '=')
    {
        return $this->repo->findFirstBy($key, $value, $operator);
    }

    /**
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     */
    public function findFirstByAttributes(array $attributes)
    {
        return $this->repo->findFirstByAttributes($attributes);
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public function firstOrCreate(array $attributes)
    {
        return $this->repo->firstOrCreate($attributes);
    }

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function get()
    {
        return $this->repo->get();
    }

    /**
     * @param array $filters
     * @param array $orders
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index($filters = [], $orders = [])
    {
        return $this->filterBy($filters)->sortBy($orders)->get();
    }

    /**
     * @param $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->repo->limit($limit);

        return $this;
    }

    /**
     * @param $perPage
     * @param $currentPage
     * @return $this
     */
    public function paginate($perPage, $currentPage)
    {
        $this->repo->paginate($perPage, $currentPage);

        return $this;
    }

    /**
     * @param array $select
     * @return $this
     */
    public function select($select = [])
    {
        $this->repo->select($select);

        return $this;
    }

    /**
     * @param $sorters
     * @return $this
     */
    public function sortBy($sorters)
    {
        $this->repo->sortBy($sorters);

        return $this;
    }

    /**
     * @param $id
     * @param $attributes
     * @return AbstractModel
     */
    public function update($id, $attributes)
    {
        /** @var AbstractModel $result */
        $result = $this->repo->update($id, $attributes);

        if (array_key_exists('attributes', $attributes)) {
            $model = $this->find($id);
            $this->eavAttributes($model, $attributes['attributes']);
        }

        return $result;
    }

    /**
     * @param $with
     * @return $this
     */
    public function with($with)
    {
        $this->repo->with($with);

        return $this;
    }
}