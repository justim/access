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
 * Create DELETE query for given table
 *
 * @author Tim <me@justim.net>
 */
class Delete extends Query
{
    /**
     * @param Table|string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     */
    public function __construct(Table|string $tableName, ?string $alias = null)
    {
        parent::__construct($tableName, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(?DriverInterface $driver = null): ?string
    {
        $driver = Database::getDriverOrDefault($driver);

        $sqlDeleteFrom = 'DELETE FROM ';

        if ($this->alias !== null) {
            $escapedAlias = $driver->escapeIdentifier($this->alias);
            $sqlDeleteFrom = "DELETE {$escapedAlias} FROM ";
        }

        $sqlDelete = $sqlDeleteFrom . $driver->escapeIdentifier($this->tableName);
        $sqlAlias = $this->getAliasSql($driver);
        $sqlJoins = $this->getJoinSql($driver);
        $sqlWhere = $this->getWhereSql($driver);
        $sqlLimit = $this->getLimitSql($driver);

        return $sqlDelete . $sqlAlias . $sqlJoins . $sqlWhere . $sqlLimit;
    }
}
