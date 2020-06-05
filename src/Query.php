<?php

/*
 * This file is part of the Access package.
 *
 * (c) Tim <me@justim.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Access;

use Access\Entity;

/**
 * Base class for building queries
 *
 * @author Tim <me@justim.net>
 */
abstract class Query
{
    // types of joins
    protected const JOIN_TYPE_LEFT = 'left-join';
    protected const JOIN_TYPE_INNER = 'inner-join';

    // prefixes for parameter placeholders
    protected const PREFIX_PARAM = 'p';
    protected const PREFIX_JOIN = 'j';
    protected const PREFIX_WHERE = 'w';
    protected const PREFIX_HAVING = 'h';
    protected const PREFIX_SUBQUERY = 's';

    // possible combiners
    private const COMBINE_WITH_AND = 'AND';
    private const COMBINE_WITH_OR = 'OR';

    /**
     * @var string
     */
    protected string $tableName;

    /**
     * @var string|null
     */
    protected ?string $alias = null;

    /**
     * @var array
     */
    protected array $where = [];

    /**
     * @var array
     */
    protected array $having = [];

    /**
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * @psalm-var array<array-key, array{type: string, tableName: string, alias: string, on: array}>
     * @var array
     */
    protected array $joins = [];

    /**
     * @var array<string, mixed>
     */
    protected array $values = [];

    /**
     * @var string[]
     */
    protected array $groupBy = [];

    /**
     * @var string|null
     */
    protected ?string $orderBy = null;

    /**
     * Create a query
     *
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     */
    protected function __construct(string $tableName, string $alias = null)
    {
        $this->tableName = $tableName;

        if (is_subclass_of($tableName, Entity::class)) {
            $this->tableName = $tableName::tableName();
        }

        if (empty($this->tableName)) {
            throw new Exception('No table given for query');
        }

        $this->alias = $alias;
    }

    /**
     * Add a left join to query
     *
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     * @param string|array<int|string, mixed> $on Join condition(s)
     * @return $this
     */
    public function leftJoin(string $tableName, string $alias, $on)
    {
        return $this->join(self::JOIN_TYPE_LEFT, $tableName, $alias, $on);
    }

    /**
     * Add a inner join to query
     *
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     * @param string|array<int|string, mixed> $on Join condition(s)
     * @return $this
     */
    public function innerJoin(string $tableName, string $alias, $on)
    {
        return $this->join(self::JOIN_TYPE_INNER, $tableName, $alias, $on);
    }

    /**
     * Add a join to query
     *
     * @param string $type Type of join (see self::JOIN_TYPE_LEFT)
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     * @param string|array<int|string, mixed> $on Join condition(s)
     * @return $this
     */
    private function join(string $type, string $tableName, string $alias, $on)
    {
        if (is_subclass_of($tableName, Entity::class)) {
            $tableName = $tableName::tableName();
        }

        $conditions = $this->processNewCondition($on, null, false, self::COMBINE_WITH_AND);

        $this->joins[] = [
            'type' => $type,
            'tableName' => $tableName,
            'alias' => $alias,
            'on' => $conditions,
        ];

        return $this;
    }

    /**
     * Add WHERE clause to query
     *
     * @param array<int|string, mixed>|string $condition List of clauses (combined with AND) or a single one
     * @param mixed $value Value of the single where clause
     * @return $this
     */
    public function where($condition, $value = null)
    {
        $newConditions = $this->processNewCondition(
            $condition,
            $value,
            func_num_args() === 2,
            self::COMBINE_WITH_AND,
        );

        $this->where = array_merge($this->where, $newConditions);

        return $this;
    }

    /**
     * Add WHERE clause to query combined with OR
     *
     * @param array<int|string, mixed> $conditions List of clauses (combined with OR)
     * @return $this
     */
    public function whereOr(array $conditions)
    {
        $newConditions = $this->processNewCondition(
            $conditions,
            null,
            false,
            self::COMBINE_WITH_OR,
        );

        $this->where = array_merge($this->where, $newConditions);

        return $this;
    }

    /**
     * Add GROUP BY to query
     *
     * Each call adds a new clause
     *
     * @param string $groupBy
     * @return $this
     */
    public function groupBy(string $groupBy)
    {
        $this->groupBy[] = $groupBy;

        return $this;
    }

    /**
     * Add a HAVING clause to query
     *
     * @param array<int|string, mixed>|string $condition List of clauses (joined it AND) or a single one
     * @param mixed $value Value of the single where clause
     * @return $this
     */
    public function having($condition, $value = null)
    {
        $newConditions = $this->processNewCondition(
            $condition,
            $value,
            func_num_args() === 2,
            self::COMBINE_WITH_AND,
        );

        $this->having = array_merge($this->having, $newConditions);

        return $this;
    }

    /**
     * Add a single ORDER BY part to query
     *
     * @param string $orderBy Order by clause
     * @return $this
     */
    public function orderBy(string $orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Add a LIMIT clause to query
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the values used in the query
     *
     * Useful for update and insert queries
     *
     * @param array<string, mixed> $values Values for the query
     * @return $this
     */
    public function values(array $values)
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Get the values with a prefixed index
     *
     * @return array The values
     */
    public function getValues(): array
    {
        $indexedValues = [];

        $i = 0;
        foreach ($this->values as $value) {
            $indexedValues[self::PREFIX_PARAM . $i] = $value;
            $i++;
        }

        foreach ($this->joins as $i => $join) {
            $this->getConditionValues(
                $indexedValues,
                $join['on'],
                self::PREFIX_JOIN . $i . self::PREFIX_JOIN,
            );
        }

        $this->getConditionValues($indexedValues, $this->where, self::PREFIX_WHERE);
        $this->getConditionValues($indexedValues, $this->having, self::PREFIX_HAVING);

        return $indexedValues;
    }

    /**
     * Get the values used in a list of conditions
     *
     * @param array $indexedValues (by ref) New condition values will added to this array
     * @param array $definition Definition of conditions
     * @param string $prefix Prefix used for the indexed values
     */
    private function getConditionValues(&$indexedValues, array $definition, string $prefix): void
    {
        $i = 0;
        foreach ($definition as $definitionPart) {
            foreach ($definitionPart['conditions'] as $condition) {
                if (!array_key_exists('value', $condition)) {
                    // where part only has a sql part, no value
                    continue;
                } elseif ($condition['value'] === null) {
                    // sql is converted to `IS NULL`
                    continue;
                } elseif ($condition['value'] === true || $condition['value'] === false) {
                    $indexedValues[$prefix . $i] = (int) $condition['value'];
                    $i++;
                    continue;
                } elseif ($condition['value'] instanceof \DateTimeInterface) {
                    $indexedValues[$prefix . $i] = $condition['value']->format(
                        Entity::DATETIME_FORMAT,
                    );
                    $i++;
                    continue;
                } elseif (is_array($condition['value'])) {
                    foreach ($condition['value'] as $conditionValuePart) {
                        $indexedValues[$prefix . $i] = $conditionValuePart;
                        $i++;
                    }

                    continue;
                }

                $indexedValues[$prefix . $i] = $condition['value'];
                $i++;
            }
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
            /** @var int $i */
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

            $onSql = $this->getConditionSql(
                'ON',
                $join['on'],
                self::PREFIX_JOIN . $i . self::PREFIX_JOIN,
            );
            $sql .= "{$escapedJoinTableName} AS {$escapedAlias}{$onSql}";

            $i++;

            return $sql;
        }, $this->joins);

        $sqlJoins = !empty($joins) ? ' ' . implode(' ', $joins) : '';

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
        return $this->getConditionSql('WHERE', $this->where, self::PREFIX_WHERE);
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
        return $this->getConditionSql('HAVING', $this->having, self::PREFIX_HAVING);
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

        $resultParts = [];

        foreach ($definition as $definitionPart) {
            $conditionParts = [];

            foreach ($definitionPart['conditions'] as $condition) {
                if (!array_key_exists('value', $condition)) {
                    $conditionParts[] = $condition['condition'];
                    continue;
                } elseif ($condition['value'] === null) {
                    $conditionParts[] = str_replace(
                        ['!= ?', '= ?'],
                        ['IS NOT NULL', 'IS NULL'],
                        $condition['condition'],
                    );

                    continue;
                } elseif (is_array($condition['value'])) {
                    $conditionParts[] = str_replace(
                        '?',
                        implode(', ', array_fill(0, count($condition['value']), '?')),
                        $condition['condition'],
                    );

                    continue;
                }

                $conditionParts[] = $condition['condition'];
            }

            $enclosedConditionParts = $conditionParts;

            // prevent double parentheses, they look ugly and are harder to read
            if (count($conditionParts) > 1) {
                $enclosedConditionParts = array_map(
                    fn($conditionPart) => "($conditionPart)",
                    $conditionParts,
                );
            }

            $combineWithSql = $this->getCombineWithSql($definitionPart['combineWith']);
            $condition = implode($combineWithSql, $enclosedConditionParts);

            $resultParts[] = $condition;
        }

        $enclosedResultParts = array_map(fn($conditionPart) => "($conditionPart)", $resultParts);

        $combineWithSql = $this->getCombineWithSql(self::COMBINE_WITH_AND);
        $condition = implode($combineWithSql, $enclosedResultParts);
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
     * Get SQL for combine with
     *
     * Ex: self::COMBINE_WITH_AND => ' AND '
     *
     * @param string $combineWith Combine conditions with (AND or OR)
     * @return string
     */
    private function getCombineWithSql(string $combineWith): string
    {
        switch ($combineWith) {
            case self::COMBINE_WITH_OR:
                return ' OR ';

            case self::COMBINE_WITH_AND:
            default:
                return ' AND ';
        }
    }

    /**
     * Escape identifier
     *
     * MySQL only
     *
     * @param string $identifier Identifier to escape
     * @return string
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
                /** @var int $i */
                return ':' . $prefix . (string) $i++;
            },
            $sql,
        );
    }

    /**
     * Process condition input
     *
     * @param array|string $condition List of clauses or a single one
     * @param mixed $value Value of the single condition
     * @param bool $valueWasProvided Was the value provided
     * @param string $combineWith Combine conditions with (AND or OR)
     * @return array
     */
    private function processNewCondition(
        $condition,
        $value,
        bool $valueWasProvided,
        string $combineWith
    ): array {
        if (!is_array($condition)) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (!is_string($condition)) {
                throw new Exception('Condition should be a string');
            }

            if ($valueWasProvided) {
                return [
                    [
                        'combineWith' => $combineWith,
                        'conditions' => [
                            [
                                'condition' => $condition,
                                'value' => $value,
                            ],
                        ],
                    ],
                ];
            }

            return [
                [
                    'combineWith' => $combineWith,
                    'conditions' => [
                        [
                            'condition' => $condition,
                        ],
                    ],
                ],
            ];
        }

        if ($value !== null) {
            throw new Exception('Values should be in condition array');
        }

        $result = [];

        foreach ($condition as $conditionCondition => $conditionValue) {
            // where part only has a sql part, no value
            if (is_int($conditionCondition)) {
                $result[] = [
                    'condition' => $conditionValue,
                ];

                continue;
            }

            $result[] = [
                'condition' => $conditionCondition,
                'value' => $conditionValue,
            ];
        }

        if (empty($result)) {
            return [];
        }

        return [
            [
                'combineWith' => $combineWith,
                'conditions' => $result,
            ],
        ];
    }
}
