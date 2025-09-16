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
use Access\Schema;

/**
 * Create USE <database> query for given schema
 *
 * @author Tim <me@justim.net>
 */
class UseDatabase extends Query
{
    private Schema $schema;

    /**
     * Create a USE <database> query
     */
    public function __construct(Schema $schema)
    {
        parent::__construct('__dummy__');

        $this->schema = $schema;
    }

    public function getSql(?DriverInterface $driver = null): string
    {
        $driver = Database::getDriverOrDefault($driver);

        return sprintf('USE %s', $driver->escapeIdentifier($this->schema->getName()));
    }
}
