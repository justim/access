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
 * Create DROP DATABASE query for given schema
 *
 * @author Tim <me@justim.net>
 */
class DropDatabase extends Query
{
    private Schema $schema;

    private bool $checkExistence = false;

    /**
     * Create a DROP DATABASE query
     */
    public function __construct(Schema $schema)
    {
        parent::__construct('__dummy__');

        $this->schema = $schema;
    }

    public function checkExistence(bool $ifNotExists = true): self
    {
        $this->checkExistence = $ifNotExists;

        return $this;
    }

    public function getSql(?DriverInterface $driver = null): string
    {
        $driver = Database::getDriverOrDefault($driver);

        return sprintf(
            'DROP DATABASE%s %s',
            $this->checkExistence ? ' IF EXISTS' : '',
            $driver->escapeIdentifier($this->schema->getName()),
        );
    }
}
