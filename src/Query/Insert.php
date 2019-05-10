<?php

declare(strict_types=1);

namespace Access\Query;

use Access\Query;

class Insert extends Query
{
    public function __construct(string $tableName, string $alias = null)
    {
        parent::__construct(self::INSERT, $tableName, $alias);
    }

    public function getSql(): ?string
    {
        $sqlInsert = 'INSERT INTO ' .
            $this->escapeIdentifier($this->tableName);
        $sqlFields = ' (' . implode(', ', array_keys($this->values)) . ')';
        $sqlValues = ' VALUES (' .
            implode(', ', array_fill(0, count($this->values), '?')) . ')';

        $sql = $sqlInsert . $sqlFields . $sqlValues;

        return $this->replaceQuestionMarks($sql, self::PREFIX_PARAM);
    }
}
