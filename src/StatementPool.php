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
use Countable;

/**
 * Query statement pool
 *
 * Keeps a pool of prepared statements
 *
 * @author Tim <me@justim.net>
 */
final class StatementPool implements Countable
{
    /**
     * All open prepared statements
     *
     * @var \PDOStatement[]
     */
    private array $stmtPool = [];

    /**
     * Database
     *
     * @var Database
     */
    private Database $db;

    /**
     * Create a pool of prepared PDO statements
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Prepare a PDO statement with SQL
     *
     * Returns previously prepared statement when available
     *
     * @param string $sql
     * @return \PDOStatement
     */
    public function prepare(string $sql): \PDOStatement
    {
        if (!isset($this->stmtPool[$sql])) {
            $this->stmtPool[$sql] = $this->db->getConnection()->prepare($sql);
        }

        return $this->stmtPool[$sql];
    }

    /**
     * Clear all prepared statements
     */
    public function clear(): void
    {
        $this->stmtPool = [];
    }

    /**
     * Get number of prepared statements
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->stmtPool);
    }
}
