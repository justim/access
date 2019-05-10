<?php

declare(strict_types=1);

namespace Access\Query;

use Access\Query;

class Delete extends Query
{
    public function __construct(string $tableName, string $alias = null)
    {
        parent::__construct(self::DELETE, $tableName, $alias);
    }

    public function getSql(): ?string
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
}
