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

use Access\Database;
use Access\Driver\DriverInterface;
use Access\Query;

/**
 * Create SAVEPOINT query
 *
 * @author Tim <me@justim.net>
 */
class Savepoint extends Query
{
    /**
     * Identifier for this savepoint
     *
     * @var string $savepoint
     */
    private string $identifier;

    /**
     * Create a SAVEPOINT query
     *
     * @param string $identifier Identifier for this savepoint
     */
    public function __construct(string $identifier)
    {
        parent::__construct('__dummy__');

        $this->identifier = $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(?DriverInterface $driver = null): ?string
    {
        $driver = Database::getDriverOrDefault($driver);

        return sprintf('SAVEPOINT %s', $driver->escapeIdentifier($this->identifier));
    }
}
