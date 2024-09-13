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
use Access\Exception;
use Access\Query;

/**
 * Manage a lock for tables
 *
 * @author Tim <me@justim.net>
 *
 */
//The actual queries are not tested because SQLite has no support for locks
class Lock
{
    /**
     * @var Database $db
     */
    private Database $db;

    /**
     * @var Query\LockTables $lockTablesQuery
     */
    private Query\LockTables $lockTablesQuery;

    /**
     * @var bool $locked
     */
    private bool $locked = false;

    /**
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->lockTablesQuery = new Query\LockTables();
    }

    /**
     * Make sure we unlock tables after use
     */
    public function __destruct()
    {
        if ($this->locked) {
            throw new Exception('Tables are still locked');
        }
    }

    /**
     * Add table for read lock
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     *
     * @param string $klass Entity class name
     * @param string $alias Lock the table by its alias
     */
    public function read(string $klass, string $alias = null): void
    {
        $this->lockTablesQuery->read($klass, $alias);
    }

    /**
     * Add a table for write lock
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     *
     * @param string $klass Entity class name
     * @param string $alias Lock the table by its alias
     */
    public function write(string $klass, string $alias = null): void
    {
        $this->db->assertValidEntityClass($klass);

        $this->lockTablesQuery->write($klass, $alias);
    }

    /**
     * Lock previously set tables
     */
    public function lock(): void
    {
        $driver = $this->db->getDriver();

        if ($this->lockTablesQuery->getSql($driver) === null) {
            return;
        }

        if (!$driver->hasLockSupport()) {
            // no support for the actual query, but keep track of
            // the state to keep the destructor logic in working
            $this->locked = true;
            return;
        }

        $this->db->query($this->lockTablesQuery);
        $this->locked = true;
    }

    /**
     * Unlock tables
     */
    public function unlock(): void
    {
        $driver = $this->db->getDriver();

        if (!$this->locked) {
            return;
        }

        if (!$driver->hasLockSupport()) {
            // no support for the actual query, but keep track of
            // the state to keep the destructor logic in working
            $this->locked = false;
            return;
        }

        $unlockTablesQuery = new Query\UnlockTables();

        $this->db->query($unlockTablesQuery);
        $this->locked = false;
    }
}
