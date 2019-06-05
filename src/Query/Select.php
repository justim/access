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
    private $virtualFields = [];

    /**
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     * @param array $virtualFields List of virtual fields, 'name' => 'SQL'
     */
    public function __construct(string $tableName, string $alias = null, array $virtualFields = [])
    {
        parent::__construct($tableName, $alias);

        $this->virtualFields = $virtualFields;
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
            $sqlFrom . $sqlAlias . $sqlJoins . $sqlWhere .
            $sqlGroupBy . $sqlHaving . $sqlOrderBy . $sqlLimit;
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

        if ($this->alias !== null) {
            $escapedAlias = $this->escapeIdentifier($this->alias);
            $sql = "SELECT {$escapedAlias}.*";
        }

        foreach ($this->virtualFields as $alias => $value) {
            $sql .= ", $value AS $alias";
        }

        return $sql;
    }
}
