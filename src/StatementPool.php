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

namespace Access;

use Access\Database;

/**
 * Query statement pool
 *
 * Keeps a pool of prepared statements
 *
 * @author Tim <me@justim.net>
 */
final class StatementPool
{
    /**
     * All open prepared statements
     *
     * @var \PDOStatement[]
     */
    private $stmtPool = [];

    /**
     * Database connection
     *
     * @var \PDO
     */
    private $connection;

    /**
     * Create a pool of prepared PDO statements
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->connection = $db->getConnection();
    }

    /**
     * Prepare a PDO statement with SQL
     *
     * Returns previously prepared statement when available
     *
     * @params string $sql
     * @return \PDOStatement
     */
    public function prepare(string $sql): \PDOStatement
    {
        if (!isset($this->stmtPool[$sql])) {
            $this->stmtPool[$sql] = $this->connection->prepare($sql);
        }

        return $this->stmtPool[$sql];
    }
}
