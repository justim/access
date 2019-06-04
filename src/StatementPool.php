<?php

declare(strict_types=1);

namespace Access;

use Access\Database;

/**
 * Query statement pool
 *
 * Keeps a pool of prepared statements
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
    private $connection = null;

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
