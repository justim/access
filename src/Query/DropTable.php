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
use Access\Schema\Table;

/**
 * Create DROP TABLE query for given table
 *
 * @author Tim <me@justim.net>
 */
class DropTable extends Query
{
    /**
     * Create a DROP TABLE query
     */
    public function __construct(Table $table)
    {
        parent::__construct($table->getName(), null);
    }

    public function getSql(?DriverInterface $driver = null): string
    {
        $driver = Database::getDriverOrDefault($driver);

        $sql = sprintf('DROP TABLE %s', $driver->escapeIdentifier($this->tableName));

        return $sql;
    }
}
