<?php

declare(strict_types=1);

namespace Access\Query;

use Access\Query;

class Raw extends Query
{
    private $sql;

    public function __construct(string $sql)
    {
        parent::__construct(self::RAW, '');

        $this->sql = $sql;
    }

    public function getQuery(): ?string
    {
        return $this->sql;
    }

    public function getValues(): array
    {
        return [];
    }
}
