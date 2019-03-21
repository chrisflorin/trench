<?php namespace Trench\Repositories;

use Carbon\Carbon;
use Trench\Models\AbstractModel;
use Trench\Services\Query\AbstractQueryBuilder;

abstract class AbstractRepository extends AbstractQueryBuilder
{
    /** @var AbstractModel $model */
    protected $model;

    protected $with = [];

    public function all()
    {
        return $this->model->all();
    }

    /**
     * @param $attributesList
     * @return mixed
     */
    public function bulkCreate($attributesList)
    {
        $now = Carbon::now();

        foreach ($attributesList as & $attributes) {
            $attributes['created_at'] = $now;
            $attributes['updated_at'] = $now;
        }

        return $this->model->insert($attributesList);
    }

    public function create($attributes) : AbstractModel
    {
        return $this->model->create($attributes);
    }

    public function destroy($id)
    {
        return $this->model->destroy($id);
    }

    public function find($id)
    {
        $query = parent::makeQuery($this->model, $this->with);

        return $query->findOrFail($id);
    }

    public function findFirstBy($key, $value, $operator = '=')
    {
        $query = parent::makeQuery($this->model, $this->with);

        return $query->where($key, $operator, $value)->first();
    }

    public function findFirstByAttributes(array $attributes = [])
    {
        $query = parent::makeQuery($this->model, $this->with);

        return $query->where($attributes)->first();
    }

    public function firstOrCreate(array $attributes = [])
    {
        return $this->model->firstOrCreate($attributes);
    }

    public function get()
    {
        $query = parent::makeQuery($this->model, $this->with);

        return parent::getUsingQuery($query);
    }

    public function update($id, $attributes)
    {
        /** @var \Illuminate\Database\Eloquent\Model $item */
        $item = $this->model->findOrFail($id);

        return $item->update($attributes);
    }

    public function with(array $with) : AbstractRepository
    {
        $this->with = array_merge($this->with, $with);

        return $this;
    }
}
