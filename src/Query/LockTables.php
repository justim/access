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
use Access\Entity;
use Access\Query;

/**
 * Create LOCK TABLES query for given tables
 *
 * @author Tim <me@justim.net>
 */
class LockTables extends Query
{
    private const TYPE_READ = 'READ';
    private const TYPE_WRITE = 'WRITE';

    /**
     * @var array<string> $locks
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

        $this->add(self::TYPE_READ, $klass, $alias);
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

        $this->add(self::TYPE_WRITE, $klass, $alias);
    }

    /**
     * Add a table to the lock
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     *
     * @param string $type Type of lock
     * @param string $klass Entity class name
     * @param string $alias Lock the table by its alias
     */
    private function add(string $type, string $klass, ?string $alias): void
    {
        if ($alias !== null) {
            $this->locks[] = sprintf(
                '%s AS %s %s',
                self::escapeIdentifier($klass::tableName()),
                self::escapeIdentifier($alias),
                $type,
            );
        } else {
            $this->locks[] = sprintf('%s %s', self::escapeIdentifier($klass::tableName()), $type);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(): ?string
    {
        if (empty($this->locks)) {
            return null;
        }

        $locks = implode(', ', $this->locks);
        $sql = sprintf('LOCK TABLES %s', $locks);

        return $sql;
    }
}
