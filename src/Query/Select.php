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

namespace Access\Query;

use Access\Query;

/**
 * Create a SELECT query for given table with optional virtual fields
 *
 * @author Tim <me@justim.net>
 */
class Select extends Query
{
    /**
     * @var array<string, string|self>
     */
    private array $virtualFields = [];

    /**
     * @var string|null $select
     */
    private ?string $select = null;

    /**
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     * @param array<string, string|self> $virtualFields List of virtual fields, 'name' => 'SQL'
     */
    public function __construct(string $tableName, string $alias = null, array $virtualFields = [])
    {
        parent::__construct($tableName, $alias);

        $this->virtualFields = $virtualFields;
    }

    /**
     * Change what you want to select
     *
     * @param string|null $select
     * @return $this
     */
    public function select($select)
    {
        $this->select = $select;

        return $this;
    }

    /**
     * Add a virtual field to select
     *
     * Will override existing virtual field with same name
     *
     * @param string $fieldName Name of the field
     * @param string $fieldValue Value of the field in SQL
     * @return $this
     */
    public function addVirtualField(string $fieldName, string $fieldValue)
    {
        $this->virtualFields[$fieldName] = $fieldValue;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(): ?string
    {
        $escapedTableName = $this->escapeIdentifier($this->tableName);

        $sqlSelect = $this->getSelectSql();
        $sqlFrom = " FROM {$escapedTableName}";
        $sqlAlias = $this->getAliasSql();
        $sqlJoins = $this->getJoinSql();
        $sqlWhere = $this->getWhereSql();
        $sqlGroupBy = $this->getGroupBySql();
        $sqlHaving = $this->getHavingSql();
        $sqlOrderBy = $this->getOrderBySql();
        $sqlLimit = $this->getLimitSql();

        return $sqlSelect .
            $sqlFrom .
            $sqlAlias .
            $sqlJoins .
            $sqlWhere .
            $sqlGroupBy .
            $sqlHaving .
            $sqlOrderBy .
            $sqlLimit;
    }

    /**
     * Get SELECT part of query, with virtual fields
     *
     * @return string
     */
    private function getSelectSql(): string
    {
        $escapedTableName = $this->escapeIdentifier($this->tableName);

        $sql = "SELECT {$escapedTableName}.*";

        if ($this->select !== null) {
            $sql = "SELECT {$this->select}";
        } elseif ($this->alias !== null) {
            $escapedAlias = $this->escapeIdentifier($this->alias);
            $sql = "SELECT {$escapedAlias}.*";
        }

        $i = 0;
        foreach ($this->virtualFields as $alias => $value) {
            $escapedAlias = $this->escapeIdentifier($alias);

            if ($value instanceof self) {
                $subSql = preg_replace(
                    '/:(([a-z][0-9]+)+)/',
                    ':' . self::PREFIX_SUBQUERY_VIRTUAL . $i . '$1',
                    (string) $value->getSql(),
                );

                $sql .= ", ($subSql) AS $escapedAlias";
                $i++;
            } else {
                $sql .= ", $value AS $escapedAlias";
            }
        }

        return $sql;
    }

    /**
     * Get the values with a prefixed index
     *
     * @return array The values
     */
    public function getValues(): array
    {
        $values = parent::getValues();

        $i = 0;

        foreach ($this->virtualFields as $value) {
            if ($value instanceof self) {
                foreach ($value->getValues() as $nestedIndex => $nestedValue) {
                    $values[self::PREFIX_SUBQUERY_VIRTUAL . $i . $nestedIndex] = $nestedValue;
                }

                $i++;
            }
        }

        return $values;
    }
}
