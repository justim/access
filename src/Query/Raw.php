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
     * @var string $sql
     */
    private $sql;

    /**
     * @var string $sql
     * @var array $values
     */
    public function __construct(string $sql, array $values = [])
    {
        parent::__construct('');

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
