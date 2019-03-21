<?php

namespace Trench\Services\Query;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Trench\Models\AbstractModel;

abstract class AbstractQueryBuilder
{
    /** @var array $appliedJoins */
    private $appliedJoins = [];

    /** @var array $appliedSelects */
    private $appliedSelects = [];

    /** @var int $currentPage */
    protected $currentPage = null;

    /** @var array $filters */
    protected $filters = [];

    /** @var array $filterBy */
    private $filterBy = [];

    /** @var bool $having */
    protected $having = false;

    /** @var array $groupBy */
    private $groupBy = [];

    /** @var array $joins */
    protected $joins = [];

    /** @var int $limit */
    private $limit = null;

    /** @var Builder $query */
    protected $query = null;

    /** @var array $select */
    private $select = [];

    /** @var array $selects */
    protected $selects = [];

    /** @var array $sortBy */
    private $sortBy = [];

    /** @var array $sorters */
    protected $sorters = [];

    protected function applyFilter(Builder $query, $filterName, $filterValue)
    {
        if (!array_key_exists($filterName, $this->filters)) {
            return;
        }

        $filter = $this->filters[$filterName];

        if (array_key_exists('groups', $filter)) {
            $groups = $filter['groups'];
            $this->applyGroups($query, $groups);
        }

        if (array_key_exists('joins', $filter)) {
            $joins = $filter['joins'];
            $this->applyJoins($query, $joins);
        }

        if (array_key_exists('selects', $filter)) {
            foreach ($filter['selects'] as $name) {
                $this->applySelect($query, $name, $filterValue);
            }
        }

        $having = array_key_exists('having', $filter) ? $filter['having'] : null;
        $where = array_key_exists('where', $filter) ? $filter['where'] : null;

        $params = [];
        if ($filter['type'] == 'array') {
            $params = $filterValue;

            $where = str_replace(':array', '?' . str_repeat(',?', count($params) - 1), $where);
            $query->whereRaw("({$where})", $params);
        } else if ($filter['type'] == 'value') {
            $params = [];
            $values = $filter['values'];

            for ($i = 0; $i < count($values); $i++) {
                $params[] = str_replace(':value', $filterValue, $values[$i]);
            }
        } else if ($filter['type'] == 'object') {
            $params = $filterValue['value'];

            if (!is_array($params)) {
                $params = [$params];
            }
        } else if ($filter['type'] == 'constant') {
            $params = [];
        }

        if (!is_null($where)) {
            $query->whereRaw("({$where})", $params);
        }

        if (!is_null($having)) {
            $this->having = true;

            $query->havingRaw("({$having})", $params);
        }
    }

    protected function applyFilters(Builder $query)
    {
        foreach ($this->filterBy as $filterName => $filterValue) {
            $this->applyFilter($query, $filterName, $filterValue);
        }
    }

    protected function applyGroup(Builder $query, $group)
    {
        if (in_array($group, $this->groupBy)) {
            return;
        }

        $query->groupBy($group);
        $this->groupBy[] = $group;
    }

    protected function applyGroups(Builder $query, array $groups)
    {
        foreach ($groups as $group) {
            $this->applyGroup($query, $group);
        }
    }

    protected function applyJoins(Builder $query, array $joins)
    {
        foreach ($joins as $joinName) {
            if (in_array($joinName, $this->appliedJoins)) {
                continue;
            }

            if (!array_key_exists($joinName, $this->joins)) {
                continue;
            }

            $join = $this->joins[$joinName];

            if (array_key_exists('prereq', $join)) {
                $this->applyJoins($query, $join['prereq']);
            }

            $params = $join['params'];
            $query->join($params[0], $params[1], $params[2], $params[3]);

            $this->appliedJoins[] = $joinName;
        }
    }

    protected function applySelect(Builder $query, $selectName, $selectValue)
    {
        if (in_array($selectName, $this->appliedSelects)) {
            return;
        }

        $select = $this->selects[$selectName];

        if (array_key_exists('groups', $select)) {
            $groups = $select['groups'];
            $this->applyGroups($query, $groups);
        }

        if (array_key_exists('joins', $select)) {
            $joins = $select['joins'];
            $this->applyJoins($query, $joins);
        }

        $params = [];
        if ($select['type'] == 'object') {
            foreach ($select['values'] as $key) {
                $params[] = $selectValue[$key];
            }
        } else if ($select['type'] == 'constant') {
            //Do nothing special
        }

        $query->selectRaw($select['select'], $params);
        $this->appliedSelects[] = $selectName;
    }

    protected function applySelects(Builder $query)
    {
        foreach ($this->select as $selectName => $selectValue) {
            $this->applySelect($query, $selectName, $selectValue);
        }
    }

    protected function applySorter(Builder $query, $sorterName, $sorterValue)
    {
        $sortKey = $sorterName;

        $sorter = $this->sorters[$sorterName];
        if (array_key_exists('sort_by', $sorter)) {
            $sortKey = $sorter['sort_by'];
        }

        if (array_key_exists('joins', $sorter)) {
            $joins = $sorter['joins'];
            $this->applyJoins($query, $joins);
        }

        if (array_key_exists('selects', $sorter)) {
            foreach ($sorter['selects'] as $name) {
                $this->applySelect($query, $name, $sorterValue);
            }
        }

        if (is_array($sorterValue)) {
            $direction = array_key_exists('direction', $sorterValue) ? $sorterValue['direction'] : 'asc';
        } else {
            $direction = $sorterValue;
        }

        $this->query = $query->orderBy($sortKey, $direction);
    }

    protected function applySorters(Builder $query)
    {
        foreach ($this->sortBy as $sorterName => $sorterValue) {
            $this->applySorter($query, $sorterName, $sorterValue);
        }
    }

    public function filterBy($filters) : AbstractQueryBuilder
    {
        $this->filterBy = array_merge($this->filterBy, $filters);

        return $this;
    }

    public function getUsingQuery(Builder $query)
    {
        $this->applyFilters($query);

        if ($this->isPaginated()) {
            $count = $this->getCount($query);

            if (!is_null($this->limit)) {
                $query->limit($this->limit);
            }

            $this->applySelects($query);
            $this->applySorters($query);

            $offset = ($this->currentPage - 1) * $this->limit;
            $result = $query->skip($offset)->get();

            return new LengthAwarePaginator($result, $count, $this->limit);
        } else {
            if (!is_null($this->limit)) {
                $query->limit($this->limit);
            }

            $this->applySorters($query);

            return $query->get();
        }
    }

    protected function getCount(Builder $query)
    {
        if (count($this->groupBy) > 0 || $this->isHaving()) {
            $countQuery = \DB::table(\DB::raw("({$query->toSql()}) as t"))->mergeBindings($query->getQuery());
            $countQuery->selectRaw('count(*) as aggregate_count');

            $result = $countQuery->get();

            $count = $result[0]->aggregate_count;
        } else {
            $count = $query->count();
        }

        return $count;
    }

    protected function isHaving()
    {
        return $this->having;
    }

    protected function isPaginated()
    {
        return $this->currentPage != null;
    }

    public function limit($limit) : AbstractQueryBuilder
    {
        $this->limit = $limit;

        return $this;
    }

    protected function makeQuery(AbstractModel $model, array $with = []) : Builder
    {
        if (empty($with)) {
            $result = $model->newQuery();
        } else {
            $result = $model->with($with);
        }

        $result->select("{$model->getTable()}.*");

        $this->resetApplied();

        return $result;
    }

    public function paginate($perPage, $currentPage = 1) : AbstractQueryBuilder
    {
        $this->limit($perPage);

        $this->currentPage = $currentPage;

        return $this;
    }

    protected function resetApplied()
    {
        $this->appliedJoins = [];
        $this->appliedSelects = [];
        $this->having = false;
    }

    public function select($select) : AbstractQueryBuilder
    {
        $this->select = array_merge($this->select, $select);

        return $this;
    }

    public function sortBy($sorters) : AbstractQueryBuilder
    {
        $this->sortBy = array_merge($this->sortBy, $sorters);

        return $this;
    }
}