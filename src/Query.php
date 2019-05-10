<?php

declare(strict_types=1);

namespace Access;

use Access\Entity;

abstract class Query
{
    protected const SELECT = 'SELECT';
    protected const INSERT = 'INSERT';
    protected const UPDATE = 'UPDATE';
    protected const DELETE = 'DELETE';
    protected const RAW = 'RAW';

    private const JOIN_TYPE_LEFT = 'left-join';
    private const JOIN_TYPE_INNER = 'inner-join';

    private const PREFIX_PARAM = 'p';
    private const PREFIX_JOIN = 'j';
    private const PREFIX_WHERE = 'w';
    private const PREFIX_HAVING = 'h';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string|null
     */
    private $alias = null;

    /**
     * @var array
     */
    private $where = [];

    /**
     * @var array
     */
    private $having = [];

    /**
     * @var int|null
     */
    private $limit = null;

    /**
     * @var array
     */
    private $joins = [];

    /**
     * @var mixed[]
     */
    private $values = [];

    /**
     * @var string[]
     */
    private $groupBy = [];

    /**
     * @var string
     */
    private $orderBy = null;

    protected function __construct(string $type, string $tableName, string $alias = null)
    {
        $this->type = $type;
        $this->tableName = $tableName;

        if (is_subclass_of($tableName, Entity::class)) {
            $this->tableName = $tableName::tableName();
        }

        $this->alias = $alias;
    }

    public function isSelect(): bool
    {
        return $this->type === self::SELECT;
    }

    public function isInsert(): bool
    {
        return $this->type === self::INSERT;
    }

    /**
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

        $this->joins[] = [
            'type' => $type,
            'tableName' => $tableName,
            'alias' => $alias,
            'on' => (array) $on,
        ];

        return $this;
    }

    public function where($condition)
    {
        if (!is_array($condition)) {
            $this->where[] = $condition;
            return $this;
        }

        $this->where = array_merge($this->where, $condition);

        return $this;
    }

    public function groupBy(string $groupBy)
    {
        $this->groupBy[] = $groupBy;

        return $this;
    }

    public function having($condition)
    {
        if (!is_array($condition)) {
            $this->having[] = $condition;
            return $this;
        }

        $this->having = array_merge($this->having, $condition);

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
     * Get the SQL query
     *
     * @return string - `null` when no query is needed
     */
    public function getQuery(): ?string
    {
        if (!isset($this->tableName)) {
            throw new Exception('No table given for query');
        }

        $result = '';

        switch ($this->type) {
            case self::SELECT:
                $result = $this->getSelectQuery();
                break;

            case self::INSERT:
                $result = $this->getInsertQuery();
                break;

            case self::UPDATE:
                $result = $this->getUpdateQuery();
                break;

            case self::DELETE:
                $result = $this->getDeleteQuery();
                break;
        }

        return $result;
    }

    private function getSelectQuery()
    {
        $escapedTableName = $this->escapeIdentifier($this->tableName);

        $sqlSelect = "SELECT {$escapedTableName}.*";
        $sqlFrom = " FROM {$escapedTableName}";
        $sqlAlias = $this->getAliasSql();

        if ($this->alias !== null) {
            $sqlSelect = "SELECT {$this->escapeIdentifier($this->alias)}.*";
        }

        $sqlJoins = $this->getJoinSql();
        $sqlWhere = $this->getWhereSql();
        $sqlGroupBy = $this->getGroupBySql();
        $sqlHaving = $this->getHavingSql();
        $sqlOrderBy = $this->getOrderBySql();
        $sqlLimit = $this->getLimitSql();

        return $sqlSelect .
            $sqlFrom . $sqlAlias . $sqlJoins . $sqlWhere .
            $sqlGroupBy . $sqlHaving . $sqlOrderBy . $sqlLimit;
    }

    private function getInsertQuery(): string
    {
        $sqlInsert = 'INSERT INTO ' .
            $this->escapeIdentifier($this->tableName);
        $sqlFields = ' (' . implode(', ', array_keys($this->values)) . ')';
        $sqlValues = ' VALUES (' .
            implode(', ', array_fill(0, count($this->values), '?')) . ')';

        $sql = $sqlInsert . $sqlFields . $sqlValues;

        return $this->replaceQuestionMarks($sql, self::PREFIX_PARAM);
    }

    private function getUpdateQuery(): ?string
    {
        $i = 0;
        $fields = implode(
            ', ',
            array_map(
                function ($q) use (&$i) {
                    $placeholder = self::PREFIX_PARAM . $i;
                    $i++;
                    return $this->escapeIdentifier($q) . ' = :' . $placeholder;
                },
                array_keys($this->values)
            )
        );

        if (empty($fields)) {
            return null;
        }

        $sqlUpdate = 'UPDATE ' . $this->escapeIdentifier($this->tableName);
        $sqlAlias = $this->getAliasSql();
        $sqlFields = ' SET ' . $fields;
        $sqlWhere = $this->getWhereSql();
        $sqlLimit = $this->getLimitSql();

        return $sqlUpdate. $sqlAlias . $sqlFields . $sqlWhere . $sqlLimit;
    }

    private function getDeleteQuery(): string
    {
        $sqlDeleteFrom = 'DELETE FROM ';

        if ($this->alias !== null) {
            $sqlDeleteFrom = "DELETE {$this->escapeIdentifier($this->alias)} FROM ";
        }

        $sqlDelete = $sqlDeleteFrom . $this->escapeIdentifier($this->tableName);
        $sqlAlias = $this->getAliasSql();
        $sqlJoins = $this->getJoinSql();
        $sqlWhere = $this->getWhereSql();
        $sqlLimit = $this->getLimitSql();

        return $sqlDelete . $sqlAlias . $sqlJoins . $sqlWhere . $sqlLimit;
    }

    /**
     * Get SQL for alias
     *
     * Ex: ' AS t'
     * Ex: ''
     *
     * @return string
     */
    private function getAliasSql(): string
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
    private function getJoinSql(): string
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
    private function getWhereSql(): string
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
    private function getHavingSql(): string
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
    private function getGroupBySql()
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
    private function getOrderBySql(): string
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
    private function getLimitSql(): string
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
    private function escapeIdentifier(string $identifier): string
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
    private function replaceQuestionMarks(string $sql, string $prefix): string
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
}
