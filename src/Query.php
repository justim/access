<?php

declare(strict_types=1);

namespace Access;

use Access\Entity;

abstract class Query
{
    protected const JOIN_TYPE_LEFT = 'left-join';
    protected const JOIN_TYPE_INNER = 'inner-join';

    protected const PREFIX_PARAM = 'p';
    protected const PREFIX_JOIN = 'j';
    protected const PREFIX_WHERE = 'w';
    protected const PREFIX_HAVING = 'h';

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string|null
     */
    protected $alias = null;

    /**
     * @var array
     */
    protected $where = [];

    /**
     * @var array
     */
    protected $having = [];

    /**
     * @var int|null
     */
    protected $limit = null;

    /**
     * @var array
     */
    protected $joins = [];

    /**
     * @var mixed[]
     */
    protected $values = [];

    /**
     * @var string[]
     */
    protected $groupBy = [];

    /**
     * @var string
     */
    protected $orderBy = null;

    /**
     * Create a query
     *
     * @param string $tableName
     * @param string $alias
     */
    protected function __construct(string $tableName, string $alias = null)
    {
        $this->tableName = $tableName;

        if (is_subclass_of($tableName, Entity::class)) {
            $this->tableName = $tableName::tableName();
        }

        if (!isset($this->tableName)) {
            throw new Exception('No table given for query');
        }

        $this->alias = $alias;
    }

    /**
     * Add a left join to query
     *
     * @param string $tableName
     * @param string $alias
     * @param string|string[] $on
     * @return $this
     */
    public function leftJoin(string $tableName, string $alias, $on)
    {
        return $this->join(
            self::JOIN_TYPE_LEFT,
            $tableName,
            $alias,
            $on
        );
    }

    /**
     * Add a inner join to query
     *
     * @param string $tableName
     * @param string $alias
     * @param string|string[] $on
     * @return $this
     */
    public function innerJoin(string $tableName, string $alias, $on)
    {
        return $this->join(
            self::JOIN_TYPE_INNER,
            $tableName,
            $alias,
            $on
        );
    }

    /**
     * @return $this
     */
    private function join(string $type, string $tableName, string $alias, $on)
    {
        if (is_subclass_of($tableName, Entity::class)) {
            $tableName = $tableName::tableName();
        }

        $conditions = $this->processNewCondition($on, null, false);
        $this->joins[] = [
            'type' => $type,
            'tableName' => $tableName,
            'alias' => $alias,
            'on' => $conditions,
        ];

        return $this;
    }

    public function where($condition, $value = null)
    {
        $newConditions = $this->processNewCondition($condition, $value, func_num_args() === 2);
        $this->where = array_merge($this->where, $newConditions);

        return $this;
    }

    public function groupBy(string $groupBy)
    {
        $this->groupBy[] = $groupBy;

        return $this;
    }

    public function having($condition, $value = null)
    {
        $newConditions = $this->processNewCondition($condition, $value, func_num_args() === 2);
        $this->having = array_merge($this->having, $newConditions);

        return $this;
    }

    public function orderBy(string $orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function values($values)
    {
        $this->values = $values;

        return $this;
    }

    public function getValues(): array
    {
        $indexedValues = [];

        $i = 0;
        foreach ($this->values as $value) {
            $indexedValues[self::PREFIX_PARAM . $i] = $value;
            $i++;
        }

        foreach ($this->joins as $i => $join) {
            $this->getConditionValues($indexedValues, $join['on'], $i . self::PREFIX_JOIN);
        }

        $this->getConditionValues($indexedValues, $this->where, self::PREFIX_WHERE);
        $this->getConditionValues($indexedValues, $this->having, self::PREFIX_HAVING);

        return $indexedValues;
    }

    private function getConditionValues(&$indexedValues, array $condition, string $prefix): void
    {
        $i = 0;
        foreach ($condition as $conditionKey => $conditionValue) {
            if (is_int($conditionKey)) {
                // where part only has a sql part, no value
                continue;
            } elseif ($conditionValue === null) {
                // sql is converted to `IS NULL`
                continue;
            } elseif ($conditionValue === true || $conditionValue === false) {
                $indexedValues[$prefix . $i] = (int) $conditionValue;
                $i++;
                continue;
            } elseif ($conditionValue instanceof \DateTimeInterface) {
                $indexedValues[$prefix . $i] = $conditionValue->format(Entity::DATETIME_FORMAT);
                $i++;
                continue;
            } elseif (is_array($conditionValue)) {
                foreach ($conditionValue as $conditionValuePart) {
                    $indexedValues[$prefix . $i] = $conditionValuePart;
                    $i++;
                }

                continue;
            }

            $indexedValues[$prefix . $i] = $conditionValue;
            $i++;
        }
    }

    /**
     * Get the SQL
     *
     * @return string - `null` when no query is needed
     */
    abstract public function getSql(): ?string;

    /**
     * Get SQL for alias
     *
     * Ex: ' AS t'
     * Ex: ''
     *
     * @return string
     */
    protected function getAliasSql(): string
    {
        $sqlAlias = '';

        if ($this->alias !== null) {
            $sqlAlias = " AS {$this->escapeIdentifier($this->alias)}";
        }

        return $sqlAlias;
    }

    /**
     * Get SQL for joins
     *
     * Ex: ' INNER JOIN `table` AS `t` ON t.ex_id = ex.id'
     * Ex: ''
     *
     * @return string
     */
    protected function getJoinSql(): string
    {
        $i = 0;

        $joins = array_map(function ($join) use (&$i) {
            $escapedJoinTableName = $this->escapeIdentifier($join['tableName']);
            $escapedAlias = $this->escapeIdentifier($join['alias']);
            $sql = '';

            switch ($join['type']) {
                case self::JOIN_TYPE_LEFT:
                    $sql .= 'LEFT JOIN ';
                    break;
                case self::JOIN_TYPE_INNER:
                    $sql .= 'INNER JOIN ';
                    break;
            }

            $onSql = $this->getConditionSql('ON', $join['on'], $i . self::PREFIX_JOIN);
            $sql .= "{$escapedJoinTableName} AS {$escapedAlias}{$onSql}";

            $i++;

            return $sql;
        }, $this->joins);

        $sqlJoins = !empty($joins) ? ' ' . implode(" ", $joins) : '';

        return $sqlJoins;
    }

    /**
     * Get SQL for where
     *
     * Ex: ' WHERE id = 1'
     * EX: ''
     *
     * @return string
     */
    protected function getWhereSql(): string
    {
        return $this->getConditionSql(
            'WHERE',
            $this->where,
            self::PREFIX_WHERE
        );
    }

    /**
     * Get SQL for where
     *
     * Ex: ' WHERE id = 1'
     * EX: ''
     *
     * @return string
     */
    protected function getHavingSql(): string
    {
        return $this->getConditionSql(
            'HAVING',
            $this->having,
            self::PREFIX_HAVING
        );
    }

    /**
     * Get SQL for condition
     *
     * Ex: ' WHERE/HAVING id = 1'
     * EX: ''
     *
     * @param string $what Type of condition (WHERE/HAVING/ON)
     * @param array $definition Definition of the condition
     * @param string $prefix Prefix for the placeholders
     * @return string
     */
    private function getConditionSql(string $what, array $definition, string $prefix): string
    {
        if (empty($definition)) {
            return '';
        }

        $conditionParts = [];

        foreach ($definition as $definitionKey => $definitionValue) {
            if (is_int($definitionKey)) {
                $conditionParts[] = $definitionValue;
                continue;
            } elseif ($definitionValue === null) {
                $conditionParts[] = str_replace(
                    [
                        '!= ?',
                        '= ?',
                    ],
                    [
                        'IS NOT NULL',
                        'IS NULL',
                    ],
                    $definitionKey
                );

                continue;
            } elseif (is_array($definitionValue)) {
                $conditionParts[] = str_replace(
                    '?',
                    implode(', ', array_fill(0, count($definitionValue), '?')),
                    $definitionKey
                );

                continue;
            }

            $conditionParts[] = $definitionKey;
        }

        $enclosedDefinitionParts = array_map(
            function ($conditionPart) {
                return "($conditionPart)";
            },
            $conditionParts
        );

        $condition = implode(' AND ', $enclosedDefinitionParts);
        $sqlCondition = " {$what} {$condition}";

        return $this->replaceQuestionMarks($sqlCondition, $prefix);
    }

    /**
     * Get SQL for group by
     *
     * Ex: ' GROUP BY id'
     * Ex: ''
     *
     * @return string
     */
    protected function getGroupBySql()
    {
        if (empty($this->groupBy)) {
            return '';
        }

        return ' GROUP BY ' . implode(' ', $this->groupBy);
    }

    /**
     * Get SQL for order
     *
     * Ex: ' ORDER BY id'
     * Ex: ''
     *
     * @return string
     */
    protected function getOrderBySql(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }

        return ' ORDER BY ' . $this->orderBy;
    }

    /**
     * Get SQL for limit
     *
     * Ex: ' LIMIT 10'
     * Ex: ''
     *
     * @return string
     */
    protected function getLimitSql(): string
    {
        if (empty($this->limit)) {
            return '';
        }

        return " LIMIT {$this->limit}";
    }

    /**
     * Escape identifier
     *
     * MySQL only
     *
     * @param string $identifier Identifier to escape
     * @return string;
     */
    protected function escapeIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Replace `?` with numbered placeholders
     *
     * @param string $sql SQL that needs its `?`'s replaces
     * @param string $prefix Prefix for numbered placeholders
     * @return string
     */
    protected function replaceQuestionMarks(string $sql, string $prefix): string
    {
        $i = 0;
        return (string) preg_replace_callback(
            '/\?/',
            function () use ($prefix, &$i) {
                return ':' . $prefix . $i++;
            },
            $sql
        );
    }

    private function processNewCondition($condition, $value, $valueWasProvided): array
    {
        if (!is_array($condition)) {
            if (!is_string($condition)) {
                throw new Exception('Condition should be a string');
            }

            if ($valueWasProvided) {
                return [
                    $condition => $value,
                ];
            }

            return [
                $condition,
            ];
        }

        if ($value !== null) {
            throw new Exception('Values should be in condition array');
        }

        return $condition;
    }
}
