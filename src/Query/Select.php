<?php

declare(strict_types=1);

namespace Access\Query;

use Access\Query;

class Select extends Query
{
    private $virtualFields = [];

    public function __construct(string $tableName, string $alias = null, array $virtualFields = [])
    {
        parent::__construct($tableName, $alias);

        $this->virtualFields = $virtualFields;
    }

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
