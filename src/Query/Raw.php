<?php

declare(strict_types=1);

namespace Access\Query;

use Access\Query;

class Raw extends Query
{
    private $sql;

    public function __construct(string $sql, array $values = [])
    {
        parent::__construct(self::RAW, '');

        $this->sql = $sql;
        $this->values = $values;
    }

    public function getSql(): ?string
    {
        return $this->sql;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
