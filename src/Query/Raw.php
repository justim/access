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
 * Create raw SQL queries
 *
 * @author Tim <me@justim.net>
 */
class Raw extends Query
{
    /**
     * @readonly
     * @var string $sql
     */
    private string $sql;

    /**
     * @var mixed[]
     */
    protected array $values = [];

    /**
     * @param string $sql
     * @param mixed[] $values
     */
    public function __construct(string $sql, array $values = [])
    {
        parent::__construct('__dummy__');

        $this->sql = $sql;
        $this->values = $values;
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
