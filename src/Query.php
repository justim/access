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

use Access\Clause\Condition\IsNull;
use Access\Clause\Condition\Raw;
use Access\Clause\ConditionInterface;
use Access\Clause\Field;
use Access\Clause\Multiple;
use Access\Clause\MultipleOr;
use Access\Clause\OrderBy\Ascending;
use Access\Clause\OrderBy\Descending;
use Access\Clause\OrderBy\Direction;
use Access\Clause\OrderBy\Random;
use Access\Clause\OrderBy\Verbatim;
use Access\Clause\OrderByInterface;
use Access\Driver\DriverInterface;
use Access\Entity;
use Access\IdentifiableInterface;
use Access\Query\QueryGeneratorState;
use Access\Query\Cursor\Cursor;
use Access\Query\IncludeSoftDeletedFilter;
use BackedEnum;

/**
 * Base class for building queries
 *
 * @author Tim <me@justim.net>
 */
abstract class Query
{
    // types of joins
    /** @var string */
    protected const JOIN_TYPE_LEFT = 'left-join';
    /** @var string */
    protected const JOIN_TYPE_INNER = 'inner-join';

    // prefixes for parameter placeholders
    /** @var string */
    protected const PREFIX_PARAM = 'p';
    /** @var string */
    protected const PREFIX_JOIN = 'j';
    /** @var string */
    protected const PREFIX_WHERE = 'w';
    /** @var string */
    protected const PREFIX_HAVING = 'h';
    /** @var string */
    protected const PREFIX_SUBQUERY_VIRTUAL = 's';
    /** @var string */
    protected const PREFIX_SUBQUERY_CONDITION = 'z';
    /** @var string */
    protected const PREFIX_ORDER = 'o';

    /**
     * Unescaped table name
     *
     * @var string
     */
    protected string $tableName;

    /**
     * Unescaped alias for table name
     *
     * @var string|null
     */
    protected ?string $alias = null;

    /**
     * @var ConditionInterface[]
     */
    protected array $where = [];

    /**
     * @var ConditionInterface[]
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
     * @psalm-var array<array-key, array{
     *  type: string,
     *  tableName: string,
     *  alias: string,
     *  on: ConditionInterface[],
     *  softDeleteCondition: IsNull|null,
     * }>
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
     * @var OrderByInterface[]
     */
    protected array $orderBy = [];

    /**
     * Condition to exclude soft deleted items
     *
     * Only filled when the entity is soft deleted
     */
    private ?IsNull $softDeleteCondition = null;

    /**
     * Include soft deleted items in the query
     *
     * By default soft deleted items are excluded
     *
     * @var IncludeSoftDeletedFilter
     */
    protected IncludeSoftDeletedFilter $includeSoftDeletedFilter = IncludeSoftDeletedFilter::Exclude;

    /**
     * Create a query
     *
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     */
    protected function __construct(string $tableName, ?string $alias = null)
    {
        $this->tableName = $tableName;

        if (is_subclass_of($tableName, Entity::class)) {
            $this->tableName = $tableName::tableName();

            if ($tableName::isSoftDeletable()) {
                /**
                 * All cases for which `?:` returns falsey should default to `$this->tableName`
                 * @psalm-suppress RiskyTruthyFalsyComparison
                 */
                $tableIdentifier = $alias ?: $this->tableName;
                $this->softDeleteCondition = new IsNull(
                    sprintf('%s.%s', $tableIdentifier, Entity::DELETED_AT_FIELD),
                );
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
     * Note: is _not_ SQL escaped for safe usage
     *
     * @return string Resolved table name
     */
    public function getRawResolvedTableName(): string
    {
        if ($this->alias !== null) {
            return $this->alias;
        }

        return $this->tableName;
    }

    /**
     * Add a left join to query
     *
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     * @param array<int|string, mixed>|string|ConditionInterface $on Join condition(s)
     * @return $this
     */
    public function leftJoin(
        string $tableName,
        string $alias,
        array|string|ConditionInterface $on,
    ): static {
        return $this->join(self::JOIN_TYPE_LEFT, $tableName, $alias, $on);
    }

    /**
     * Add a inner join to query
     *
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     * @param array<int|string, mixed>|string|ConditionInterface $on Join condition(s)
     * @return $this
     */
    public function innerJoin(
        string $tableName,
        string $alias,
        array|string|ConditionInterface $on,
    ): static {
        return $this->join(self::JOIN_TYPE_INNER, $tableName, $alias, $on);
    }

    /**
     * Add a join to query
     *
     * @param string $type Type of join (see self::JOIN_TYPE_LEFT)
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     * @param array<int|string, mixed>|string|ConditionInterface $on Join condition(s)
     * @psalm-suppress RedundantConditionGivenDocblockType The $on input is checked
     * @return $this
     */
    private function join(
        string $type,
        string $tableName,
        string $alias,
        array|string|ConditionInterface $on,
    ): static {
        $onClause = [];
        $softDeleteCondition = null;

        if (is_subclass_of($tableName, Entity::class)) {
            if ($tableName::isSoftDeletable()) {
                $tableIdentifier = $alias ?: $tableName::tableName();
                $softDeleteCondition = new IsNull(
                    sprintf('%s.%s', $tableIdentifier, Entity::DELETED_AT_FIELD),
                );
            }

            $tableName = $tableName::tableName();
        }

        $onClause[] = new Multiple(...$this->processNewCondition($on, null, false));

        $this->joins[] = [
            'type' => $type,
            'tableName' => $tableName,
            'alias' => $alias,
            'on' => $onClause,
            'softDeleteCondition' => $softDeleteCondition,
        ];

        return $this;
    }

    /**
     * Add WHERE clause to query
     *
     * @param array<int|string, mixed>|string|ConditionInterface $condition List of clauses (combined with AND) or a single one
     * @param mixed $value Value of the single where clause
     * @return $this
     */
    public function where(array|string|ConditionInterface $condition, mixed $value = null): static
    {
        $newCondition = $this->processNewCondition($condition, $value, func_num_args() === 2);

        $this->where[] = new Multiple(...$newCondition);

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
        $newCondition = $this->processNewCondition($conditions, null, false);

        $this->where[] = new MultipleOr(...$newCondition);

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
     * @param array<int|string, mixed>|string|ConditionInterface $condition List of clauses (joined it AND) or a single one
     * @param mixed $value Value of the single where clause
     * @return $this
     */
    public function having(array|string|ConditionInterface $condition, mixed $value = null): static
    {
        $newCondition = $this->processNewCondition($condition, $value, func_num_args() === 2);

        $this->having[] = new Multiple(...$newCondition);

        return $this;
    }

    /**
     * Add a single/multiple ORDER BY part(s) to query
     *
     * @param string|OrderByInterface|string[]|OrderByInterface $orderBy Order by clause
     * @return $this
     */
    public function orderBy(string|OrderByInterface|array $orderBy): static
    {
        if (is_array($orderBy)) {
            foreach ($orderBy as $order) {
                $this->orderBy($order);
            }

            return $this;
        }

        if (is_string($orderBy)) {
            if (str_contains($orderBy, ',')) {
                return $this->orderBy(array_map('trim', explode(',', $orderBy)));
            } elseif (preg_match('/^rand(om)?\(\)$/i', $orderBy)) {
                $orderBy = new Random();
            } elseif (preg_match('/^(.*) (asc|desc)$/i', $orderBy, $matches)) {
                // can be anything, treat it as a raw field/expression
                $field = new Raw($matches[1]);

                $direction = match (strtolower($matches[2])) {
                    'asc' => Direction::Ascending,
                    'desc' => Direction::Descending,
                    default => throw new Exception('Invalid direction'),
                };

                $orderBy = match ($direction) {
                    Direction::Ascending => new Ascending($field),
                    Direction::Descending => new Descending($field),
                };
            } else {
                $field = new Raw($orderBy);
                $orderBy = new Verbatim($field);
            }
        }

        $this->orderBy[] = $orderBy;

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
    public function getValues(?DriverInterface $driver = null): array
    {
        $driver = Database::getDriverOrDefault($driver);

        /** @var array<string, mixed> $indexedValues */
        $indexedValues = [];

        $i = 0;
        /** @var mixed $value */
        foreach ($this->values as $value) {
            // this is not a value, but a direct reference to a field, no need for an indexed value
            if (!$value instanceof Field) {
                $index = self::PREFIX_PARAM . $i;

                /** @psalm-suppress MixedAssignment */
                $indexedValues[$index] = $this->toDatabaseFormat($value);
                $i++;
            }
        }

        foreach ($this->joins as $i => $join) {
            $joinConditions = $this->preprocessConditions(
                $join['on'],
                $join['softDeleteCondition'],
            );

            $this->getConditionValues(
                $driver,
                $indexedValues,
                $joinConditions,
                self::PREFIX_JOIN . $i . self::PREFIX_JOIN,
            );
        }

        $where = $this->preprocessConditions($this->where, $this->softDeleteCondition);

        $this->getConditionValues($driver, $indexedValues, $where, self::PREFIX_WHERE);
        $this->getConditionValues($driver, $indexedValues, $this->having, self::PREFIX_HAVING);
        $this->getConditionValues($driver, $indexedValues, $this->orderBy, self::PREFIX_ORDER);

        return $indexedValues;
    }

    /**
     * Get the values used in a list of conditions
     *
     * @param array<string, mixed> $indexedValues (by ref) New condition values will added to this array
     * @param ConditionInterface[]|OrderByInterface[] $definition Definition of conditions
     * @param string $prefix Prefix used for the indexed values
     */
    private function getConditionValues(
        DriverInterface $driver,
        &$indexedValues,
        array $definition,
        string $prefix,
    ): void {
        $state = new QueryGeneratorState($driver, $prefix, self::PREFIX_SUBQUERY_CONDITION);

        foreach ($definition as $condition) {
            $condition->injectConditionValues($state);
        }

        $indexedValues = array_merge($indexedValues, $state->getIndexedValues());
    }

    /**
     * Convert a value in a database usable format
     *
     * @psalm-template T
     * @param mixed $value Any value
     * @psalm-param T $value Any value
     * @return mixed Database usable format
     * @psalm-return (T is Collection ? int[] : (T is null ? null : (T is bool ? int : (T is array ? mixed[] : (T is \ArrayIterator ? mixed[] : (T is BackedEnum ? string|int : (T is int ? int : string)))))))  Database usable format
     * @internal
     */
    public static function toDatabaseFormat(mixed $value): mixed
    {
        if ($value instanceof IdentifiableInterface) {
            /** @psalm-suppress MixedAssignment */
            $id = $value->getId();

            if (is_int($id)) {
                return $id;
            }

            return self::toDatabaseFormat($id);
        }

        if ($value instanceof Collection) {
            return $value->getIds();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(Entity::DATETIME_FORMAT);
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        if (is_int($value)) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \ArrayIterator) {
            // convert to array
            /** @var mixed[] $value */
            $value = iterator_to_array($value);
        }

        if (is_array($value)) {
            return array_map(
                /** @param mixed $itemValue
                 * @return mixed */
                fn($itemValue) => self::toDatabaseFormat($itemValue),
                $value,
            );
        }

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Get the SQL
     *
     * @return string - `null` when no query is needed
     */
    abstract public function getSql(?DriverInterface $driver = null): ?string;

    /**
     * Get SQL for alias
     *
     * Ex: ' AS t'
     * Ex: ''
     *
     * @return string
     */
    protected function getAliasSql(DriverInterface $driver): string
    {
        $sqlAlias = '';

        if ($this->alias !== null) {
            $escaptedAlias = $driver->escapeIdentifier($this->alias);
            $sqlAlias = " AS {$escaptedAlias}";
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
    protected function getJoinSql(DriverInterface $driver): string
    {
        $i = 0;

        $joins = array_map(function ($join) use ($driver, &$i) {
            /** @var int $i */
            $escapedJoinTableName = $driver->escapeIdentifier($join['tableName']);
            $escapedAlias = $driver->escapeIdentifier($join['alias']);
            $sql = '';

            switch ($join['type']) {
                case self::JOIN_TYPE_LEFT:
                    $sql .= 'LEFT JOIN ';
                    break;
                case self::JOIN_TYPE_INNER:
                    $sql .= 'INNER JOIN ';
                    break;
            }

            $joinConditions = $this->preprocessConditions(
                $join['on'],
                $join['softDeleteCondition'],
            );

            $onSql = $this->getConditionSql(
                $driver,
                'ON',
                $joinConditions,
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
    protected function getWhereSql(DriverInterface $driver): string
    {
        $where = $this->preprocessConditions($this->where, $this->softDeleteCondition);

        return $this->getConditionSql($driver, 'WHERE', $where, self::PREFIX_WHERE);
    }

    /**
     * Get SQL for where
     *
     * Ex: ' WHERE id = 1'
     * EX: ''
     *
     * @return string
     */
    protected function getHavingSql(DriverInterface $driver): string
    {
        return $this->getConditionSql($driver, 'HAVING', $this->having, self::PREFIX_HAVING);
    }

    /**
     * Get SQL for condition
     *
     * Ex: ' WHERE/HAVING id = 1'
     * EX: ''
     *
     * @param string $what Type of condition (WHERE/HAVING/ON)
     * @param ConditionInterface[]|OrderByInterface[] $definition Definition of the condition
     * @param string $prefix Prefix for the placeholders
     * @return string
     */
    private function getConditionSql(
        DriverInterface $driver,
        string $what,
        array $definition,
        string $prefix,
        string $combinator = ' AND ',
    ): string {
        if (empty($definition)) {
            return '';
        }

        $conditions = [];
        $state = new QueryGeneratorState($driver, $prefix, self::PREFIX_SUBQUERY_CONDITION);

        foreach ($definition as $definitionPart) {
            $conditions[] = $definitionPart->getConditionSql($state);
        }

        $condition = implode($combinator, $conditions);
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
    protected function getGroupBySql(): string
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
    protected function getOrderBySql(DriverInterface $driver): string
    {
        return $this->getConditionSql(
            $driver,
            'ORDER BY',
            $this->orderBy,
            self::PREFIX_ORDER,
            ', ',
        );
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
        /**
         * All cases for which `empty` returns falsey should default to an empty string
         * @psalm-suppress RiskyTruthyFalsyComparison
         */
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
     * @param array<mixed>|string|ConditionInterface $condition List of clauses or a single one
     * @param mixed $value Value of the single condition
     * @param bool $valueWasProvided Was the value provided
     * @return ConditionInterface[]
     */
    private function processNewCondition(
        array|string|ConditionInterface $condition,
        mixed $value,
        bool $valueWasProvided,
    ): array {
        if (!is_array($condition)) {
            /**
             * @psalm-suppress DocblockTypeContradiction
             * @phpstan-ignore booleanAnd.alwaysFalse
             */
            if (!is_string($condition) && !$condition instanceof ConditionInterface) {
                throw new Exception('Condition should be a string or `ConditionInterface`');
            }

            if ($condition instanceof ConditionInterface) {
                return [$condition];
            }

            if ($valueWasProvided) {
                return [new Raw($condition, $value)];
            }

            return [new Raw($condition)];
        }

        if ($value !== null) {
            throw new Exception('Values should be in condition array');
        }

        $result = [];

        /** @var mixed $conditionValue */
        foreach ($condition as $conditionCondition => $conditionValue) {
            // where part only has a sql part, no value
            if (is_int($conditionCondition)) {
                if ($conditionValue instanceof ConditionInterface) {
                    $result[] = $conditionValue;
                } elseif (is_string($conditionValue)) {
                    $result[] = new Raw($conditionValue);
                } else {
                    throw new Exception('Condition should be a string or `ConditionInterface`');
                }

                continue;
            }

            $result[] = new Raw($conditionCondition, $conditionValue);
        }

        return $result;
    }

    /**
     * @param ConditionInterface[] $conditions
     * @return ConditionInterface[]
     */
    private function preprocessConditions(array $conditions, ?IsNull $softDeleteCondition): array
    {
        $conditions_ = [];

        if (
            $softDeleteCondition !== null &&
            $this->includeSoftDeletedFilter === IncludeSoftDeletedFilter::Exclude
        ) {
            $conditions_[] = $softDeleteCondition;
        }

        array_push($conditions_, ...$conditions);

        return $conditions_;
    }

    /**
     * Set include soft deleted items in the query
     *
     * All queries/join that are used by this query will also include soft deleted
     *
     * @return IncludeSoftDeletedFilter The old value
     */
    public function setIncludeSoftDeleted(
        IncludeSoftDeletedFilter|bool $includeSoftDeleted,
    ): IncludeSoftDeletedFilter {
        $oldValue = $this->includeSoftDeletedFilter;

        if (is_bool($includeSoftDeleted)) {
            $includeSoftDeleted = $includeSoftDeleted
                ? IncludeSoftDeletedFilter::Include
                : IncludeSoftDeletedFilter::Exclude;
        }

        if ($includeSoftDeleted !== IncludeSoftDeletedFilter::Auto) {
            $this->includeSoftDeletedFilter = $includeSoftDeleted;
        }

        return $oldValue;
    }
}
