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
 * Skip code coverage because testing this is not possible with SQLite,
 * re-enable when we start testing this with a different db driver.
 * @codeCoverageIgnore
 */
class Lock
{
    /**
     * @var Database $db
     */
    private Database $db;

    /**
     * @var string[] $locks
     */
    private array $locks = [];

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
     */
    public function read(string $klass): void
    {
        $this->db->assertValidEntityClass($klass);

        $this->locks[] = "{$klass::tableName()} READ";
    }

    /**
     * Add a table for write lock
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     *
     * @param string $klass Entity class name
     */
    public function write(string $klass): void
    {
        $this->db->assertValidEntityClass($klass);

        $this->locks[] = "{$klass::tableName()} WRITE";
    }

    /**
     * Lock previously set tables
     */
    public function lock(): void
    {
        if (empty($this->locks)) {
            return;
        }

        $locks = implode(', ', $this->locks);

        $lockQuery = new Query\Raw(
            sprintf(
                'LOCK TABLES %s',
                $locks
            )
        );

        $this->db->query($lockQuery);
        $this->locked = true;
    }

    /**
     * Unlock tables
     */
    public function unlock(): void
    {
        if (!$this->locked) {
            return;
        }

        $unlockQuery = new Query\Raw(
            'UNLOCK TABLES',
        );

        $this->db->query($unlockQuery);
        $this->locked = false;
    }
}
