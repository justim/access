<?php

declare(strict_types=1);

namespace Access\Query;

use Access\Query;

class Update extends Query
{
    public function __construct(string $tableName, string $alias = null)
    {
        parent::__construct(self::UPDATE, $tableName, $alias);
    }
}
