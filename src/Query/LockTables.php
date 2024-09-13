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
use Access\Entity;
use Access\Query;

/**
 * Create LOCK TABLES query for given tables
 *
 * @author Tim <me@justim.net>
 */
class LockTables extends Query
{
    /**
     * @var array<array{LockType, class-string<Entity>, ?string}> $locks
     */
    private array $locks = [];

    /**
     * Create a LOCK TABLES query
     *
     * Add tables by calling `read` and/or `write`
     */
    public function __construct()
    {
        parent::__construct('__dummy__');
    }

    /**
     * Add table for read lock
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     *
     * @param string $klass Entity class name
     * @param string|null $alias Lock the table by its alias
     */
    public function read(string $klass, ?string $alias = null): void
    {
        Database::assertValidEntityClass($klass);

        $this->add(LockType::Read, $klass, $alias);
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
    public function write(string $klass, ?string $alias = null): void
    {
        Database::assertValidEntityClass($klass);

        $this->add(LockType::Write, $klass, $alias);
    }

    /**
     * Add a table to the lock
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     *
     * @param LockType $type Type of lock
     * @param string $klass Entity class name
     * @param string $alias Lock the table by its alias
     */
    private function add(LockType $type, string $klass, ?string $alias): void
    {
        $this->locks[] = [$type, $klass, $alias];
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(?DriverInterface $driver = null): ?string
    {
        if (empty($this->locks)) {
            return null;
        }

        $driver = Database::getDriverOrDefault($driver);

        $locks = [];

        foreach ($this->locks as [$type, $klass, $alias]) {
            if ($alias !== null) {
                $locks[] = sprintf(
                    '%s AS %s %s',
                    $driver->escapeIdentifier($klass::tableName()),
                    $driver->escapeIdentifier($alias),
                    $type->value,
                );
            } else {
                $locks[] = sprintf(
                    '%s %s',
                    $driver->escapeIdentifier($klass::tableName()),
                    $type->value,
                );
            }
        }

        $locks = implode(', ', $locks);
        $sql = sprintf('LOCK TABLES %s', $locks);

        return $sql;
    }
}
