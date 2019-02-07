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
}
