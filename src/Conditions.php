<?php

namespace Tivins\Database;

/**
 *
 */
class Conditions
{
    const MODE_AND = 'and';
    const MODE_OR  = 'or';

    protected array $conditions = [];
    protected array $nestedConds = [];
    protected string $mode = self::MODE_AND;

    public function __construct(string $mode = self::MODE_AND)
    {
        $this->mode = $mode;
    }

    /**
     *
     */
    public function whereIn(string $field, array $values)
    {
        $stmt = "$field in (".implode(',',array_fill(0, count($values), '?')).")";
        $this->pushCondition($stmt, $values);
        return $this;
    }

    /**
     *
     */
    public function like(string $field, string $value)
    {
        $this->pushCondition("$field like ?", [$value]);
        return $this;
    }

    /**
     *
     */
    public function condition($field, $value = null, $operator = '=')
    {
        if ($field instanceof Conditions) {
            $this->nestedConds[] = $field;
            return $this;
        }

        if ($operator == 'in') return $this->whereIn($field, $value);
        if ($operator == 'like') return $this->like($field, $value);
        if (!in_array($operator, ['<','<=','=','!=','>=','>'])) throw new Exception('Invalid operator');
        $this->pushCondition("$field $operator ?", [$value]);
        return $this;
    }

    /**
     *
     */
    public function buildConditions()
    {
        if (empty($this->conditions) &&
            empty($this->nestedConds))
            return['', []];

        $query = implode(' ' . $this->mode . ' ', array_column($this->conditions, 'cond'));
        if ($this->mode == self::MODE_OR) $query = "($query)";
        $parameters = array_flatten(array_column($this->conditions, 'data'));

        foreach ($this->nestedConds as $nestedConds) {
            list($subquery, $subparameters) = $nestedConds->buildConditions();
            if (!empty($subquery)) {
                $query .= (empty($query) ? '' : ' ' . $this->mode . ' ') . $subquery;
                $parameters = array_merge($parameters, $subparameters);
            }
        }

        return [$query, $parameters];
    }

    /**
     *
     */
    private function pushCondition(string $condition, array $data)
    {
        $this->conditions[] = [
            'cond' => $condition,
            'data' => $data,
        ];
    }
}
