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
use Access\IdentifiableInterface;
use Access\Query\Cursor\Cursor;
use Access\Query\Select;

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
    protected const PREFIX_SUBQUERY_VIRTUAL = 's';
    protected const PREFIX_SUBQUERY_CONDITION = 'z';

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
     * @var int|null
     */
    protected ?int $offset = null;

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

            if ($tableName::isSoftDeletable()) {
                $tableIdentifier = $alias ?: $this->tableName;
                $deletedAtCondition = sprintf(
                    '%s.%s IS NULL',
                    $this->escapeIdentifier($tableIdentifier),
                    $this->escapeIdentifier(Entity::DELETED_AT_FIELD),
                );

                $this->where($deletedAtCondition);
            }
        }

        if (empty($this->tableName)) {
            throw new Exception('No table given for query');
        }

        $this->alias = $alias;
    }

    /**
     * Get the resolved table name
     *
     * Meaning, the table name, or its alias
     *
     * Note: is SQL escaped for safe usage
     *
     * @return string Resolved table name
     */
    public function getResolvedTableName(): string
    {
        if ($this->alias !== null) {
            return $this->escapeIdentifier($this->alias);
        }

        return $this->tableName;
    }

    /**
     * Add a left join to query
     *
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     * @param string|array<int|string, mixed> $on Join condition(s)
     * @return $this
     */
    public function leftJoin(string $tableName, string $alias, array|string $on): static
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
    public function innerJoin(string $tableName, string $alias, array|string $on): static
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
     * @psalm-suppress RedundantConditionGivenDocblockType The $on input is checked
     * @return $this
     */
    private function join(string $type, string $tableName, string $alias, array|string $on): static
    {
        if (is_subclass_of($tableName, Entity::class)) {
            if ($tableName::isSoftDeletable()) {
                $tableIdentifier = $alias ?: $tableName::tableName();
                $deletedAtCondition = sprintf(
                    '%s.%s IS NULL',
                    $this->escapeIdentifier($tableIdentifier),
                    $this->escapeIdentifier(Entity::DELETED_AT_FIELD),
                );

                if (is_string($on)) {
                    $on = [$deletedAtCondition, $on];
                } elseif (is_array($on)) {
                    array_unshift($on, $deletedAtCondition);
                }
            }

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
    public function where(array|string $condition, mixed $value = null): static
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
    public function whereOr(array $conditions): static
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
    public function groupBy(string $groupBy): static
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
    public function having(array|string $condition, mixed $value = null): static
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
    public function orderBy(string $orderBy): static
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Add a LIMIT clause to query
     *
     * @param int $limit
     * @param int|null $offset
     * @return $this
     */
    public function limit(int $limit, ?int $offset = null): static
    {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Apply a pagination cursor to query
     *
     * @param Cursor $cursor Pagination cursor
     * @return $this
     */
    public function applyCursor(Cursor $cursor): static
    {
        $cursor->apply($this);

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
    public function values(array $values): static
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Get the values with a prefixed index
     *
     * @return array<string, mixed> The values
     */
    public function getValues(): array
    {
        /** @var array<string, mixed> $indexedValues */
        $indexedValues = [];

        $i = 0;
        /** @var mixed $value */
        foreach ($this->values as $value) {
            $index = self::PREFIX_PARAM . $i;

            /** @psalm-suppress MixedAssignment */
            $indexedValues[$index] = $this->toDatabaseFormat($value);
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
     * @param array<string, mixed> $indexedValues (by ref) New condition values will added to this array
     * @param ConditionInterface[] $definition Definition of conditions
     * @param string $prefix Prefix used for the indexed values
     */
    private function getConditionValues(&$indexedValues, array $definition, string $prefix): void
    {
        $i = 0;
        $subQueryIndex = 0;
        foreach ($definition as $definitionPart) {
            foreach ($definitionPart['conditions'] as $condition) {
                if (!array_key_exists('value', $condition)) {
                    // where part only has a sql part, no value
                    continue;
                } elseif ($condition['value'] instanceof Select) {
                    foreach ($condition['value']->getValues() as $nestedIndex => $nestedValue) {
                        $indexedValues[
                            self::PREFIX_SUBQUERY_CONDITION . $subQueryIndex . $nestedIndex
                        ] = $nestedValue;
                    }

                    $subQueryIndex++;
                    continue;
                } elseif ($condition['value'] === null) {
                    // sql is converted to `IS NULL`
                    continue;
                } elseif (
                    is_array($condition['value']) ||
                    $condition['value'] instanceof Collection
                ) {
                    $values = $this->toDatabaseFormat($condition['value']);

                    foreach ($values as $itemValue) {
                        $indexedValues[$prefix . $i] = $itemValue;
                        $i++;
                    }

                    continue;
                }

                $indexedValues[$prefix . $i] = $this->toDatabaseFormat($condition['value']);
                $i++;
            }
        }
    }

    /**
     * Convert a value in a database usable format
     *
     * @param mixed $value Any value
     * @return mixed Database usable format
     */
    private function toDatabaseFormat(mixed $value): mixed
    {
        if ($value instanceof IdentifiableInterface) {
            return $this->toDatabaseFormat($value->getId());
        }

        if ($value instanceof Collection) {
            return $value->getIds();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(Entity::DATETIME_FORMAT);
        }

        if ($value === true || $value === false) {
            return (int) $value;
        }

        if (is_array($value)) {
            return array_map(fn($itemValue) => $this->toDatabaseFormat($itemValue), $value);
        }

        return $value;
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
     * @param ConditionInterface[] $definition Definition of the condition
     * @param string $prefix Prefix for the placeholders
     * @return string
     */
    private function getConditionSql(string $what, array $definition, string $prefix): string
    {
        if (empty($definition)) {
            return '';
        }

        $resultParts = [];
        $subQueryIndex = 0;

        foreach ($definition as $definitionPart) {
            /** @var string[] $conditionParts */
            $conditionParts = [];

            foreach ($definitionPart['conditions'] as $condition) {
                if (!array_key_exists('value', $condition)) {
                    $conditionParts[] = $condition['condition'];
                    continue;
                } elseif ($condition['value'] instanceof Select) {
                    $subQuery = preg_replace(
                        '/:(([a-z][0-9]+)+)/',
                        ':' . self::PREFIX_SUBQUERY_CONDITION . $subQueryIndex . '$1',
                        (string) $condition['value']->getSql(),
                    );

                    $conditionParts[] = preg_replace(
                        ['/(!)?= ?\?/', '/(NOT)? IN ?\(\?\)/i'],
                        [sprintf('$1= (%s)', $subQuery), sprintf('$1 IN (%s)', $subQuery)],
                        $condition['condition'],
                    );

                    $subQueryIndex++;
                    continue;
                } elseif ($condition['value'] === null) {
                    $conditionParts[] = preg_replace_callback_array(
                        [
                            '/!= ?\?/' => fn() => 'IS NOT NULL',
                            '/= ?\?/' => fn() => 'IS NULL',
                        ],
                        $condition['condition'],
                    );

                    continue;
                } elseif (
                    is_array($condition['value']) ||
                    $condition['value'] instanceof Collection
                ) {
                    $values =
                        $condition['value'] instanceof Collection
                            ? $condition['value']->getIds()
                            : $condition['value'];

                    $conditionParts[] = str_replace(
                        '?',
                        implode(', ', array_fill(0, count($values), '?')),
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

        return ' GROUP BY ' . implode(', ', $this->groupBy);
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

        $limitSql = " LIMIT {$this->limit}";

        if ($this->offset !== null) {
            $limitSql .= " OFFSET {$this->offset}";
        }

        return $limitSql;
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
        return str_replace('.', '`.`', sprintf('`%s`', str_replace('`', '``', $identifier)));
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
        array|string $condition,
        mixed $value,
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

        /** @var mixed $conditionValue */
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
