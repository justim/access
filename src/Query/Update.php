<?php

declare(strict_types=1);

namespace Access\Query;

use Access\Query;

class Update extends Query
{
    public function __construct(string $tableName, string $alias = null)
    {
        parent::__construct($tableName, $alias);
    }

    public function getSql(): ?string
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
}
