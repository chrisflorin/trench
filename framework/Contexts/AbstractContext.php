<?php

namespace Trench\Contexts;

abstract class AbstractContext
{
    protected $blacklistAttributes = [];

    protected $contextChain = [];

    protected $select = [];

    protected $with = [];

    public function isAllowed()
    {
        return true;
    }

    public function scrub(array $data)
    {
        $result = [];

        foreach ($data as $row) {
            foreach ($this->blacklistAttributes as $attribute) {
                if (array_key_exists($attribute, $row)) {
                    unset($row[$attribute]);
                }
            }

            foreach ($row as $key => $value) {
                if (array_key_exists($key, $this->contextChain)) {
                    /** @var AbstractContext $context */
                    $context = app($this->contextChain[$key]);
                    $row[$key] = $context->scrub([$value])[0];
                }
            }

            $result[] = $row;
        }

        return $result;
    }

    public function select($method)
    {
        if (!array_key_exists($method, $this->select)) {
            return [];
        }

        return $this->select[$method];
    }

    public function with($method)
    {
        if (!array_key_exists($method, $this->with)) {
            return [];
        }

        return $this->with[$method];
    }
}
