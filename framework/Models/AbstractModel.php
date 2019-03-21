<?php

namespace Trench\Models;

use Illuminate\Database\Eloquent\Model;
use Trench\Models\Attribute\Attribute;

abstract class AbstractModel extends Model
{
    /** @var string $attributesTable */
    protected $attributesTable = null;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, $this->attributesTable)
            ->withPivot(['value']);
    }

    /**
     * @param array $attributes
     * @return AbstractModel
     */
    public static function create(array $attributes = []) : AbstractModel
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    /**
     * @param $field
     * @param $value
     * @param string $operator
     * @return Model|null|object|static
     */
    public function findFirstBy($field, $value, $operator = '=')
    {
        $query = $this->with($this->with);

        return $query->where($field, $operator, $value)->first();
    }

    /**
     * @return array
     */
    public function getAttributesAsArray()
    {
        $attributeList = $this->attributes()->get();

        $result = [];

        /** @var Attribute $attribute */
        foreach ($attributeList as $attribute) {
            $pivot = $attribute->pivot;
            $result[$attribute->name] = $pivot->value;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getAttributesTable()
    {
        return $this->attributesTable;
    }

    /**
     * @return bool
     */
    protected function hasAttributes()
    {
        return !is_null($this->attributesTable);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = parent::toArray();

        if ($this->hasAttributes()) {
            $result['attributes'] = $this->getAttributesAsArray();
        }

        return $result;
    }
}