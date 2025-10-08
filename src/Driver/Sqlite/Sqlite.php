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

namespace Access\Driver\Sqlite;

use Access\Clause\Field;
use Access\Driver\Driver;
use Access\Driver\Query\AlterTableBuilderInterface;
use Access\Driver\Query\CreateDatabaseBuilderInterface;
use Access\Driver\Query\CreateTableBuilderInterface;
use Access\Driver\SqlTypeDefinitionBuilderInterface;
use Access\Driver\Sqlite\Query\AlterTableBuilder;
use Access\Driver\Sqlite\Query\CreateDatabaseBuilder;
use Access\Driver\Sqlite\Query\CreateTableBuilder;
use Access\Driver\Sqlite\SqliteSqlTypeDefinitionBuilder;
use Access\Exception\NotSupportedException;
use Access\Exception\TableDoesNotExistException;
use Access\ReadLock;
use Access\Schema\Index;

/**
 * SQLite specific driver
 *
 * @author Tim <me@justim.net>
 * @internal
 * @psalm-suppress MissingConstructor
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress RedundantPropertyInitializationCheck
 */
class Sqlite extends Driver
{
    public const NAME = 'sqlite';

    private SqlTypeDefinitionBuilderInterface $sqlTypeDefinition;
    private CreateDatabaseBuilder $createDatabaseBuilder;
    private CreateTableBuilder $createTableBuilder;
    private AlterTableBuilder $alterTableBuilder;

    /**
     * Escape identifier
     *
     * @param string|Field $identifier Identifier to escape
     * @return string
     * @internal
     */
    public function escapeIdentifier(string|Field $identifier): string
    {
        if ($identifier instanceof Field) {
            $identifier = $identifier->getName();
        }

        return str_replace('.', '"."', sprintf('"%s"', str_replace('"', '""', $identifier)));
    }

    /**
     * Get a debug string value for a value in SQLite dialect
     *
     * Useful for the debug query, should not be used otherwise, use prepared statements
     */
    public function getDebugStringValue(mixed $value): string
    {
        return sprintf("'%s'", addslashes((string) $value));
    }

    /**
     * Convert a PDOException to a more specific Exception
     */
    public function convertPdoException(\PDOException $e): ?\Exception
    {
        $message = $e->getMessage();

        if (strpos($message, 'no such table') !== false) {
            return new TableDoesNotExistException(
                sprintf('Table does not exists: %s', $message),
                0,
                $e,
            );
        }

        return null;
    }

    /**
     * Get the function name for random in SQLite dialect
     */
    public function getFunctionNameRandom(): string
    {
        return 'RANDOM()';
    }

    /**
     * Has the SQLite driver support for LOCK/UNLOCK TABLES?
     */
    public function hasLockSupport(): bool
    {
        return false;
    }

    /**
     * Get the SQL for a read lock
     */
    public function getReadLockSql(ReadLock $readLock): string
    {
        return '';
    }

    public function getSqlTypeDefinitionBuilder(): SqlTypeDefinitionBuilderInterface
    {
        return $this->sqlTypeDefinition ??= new SqliteSqlTypeDefinitionBuilder($this);
    }

    public function getSqlIndexDefinition(Index $index): string
    {
        throw new NotSupportedException('Creating indexes for SQLite not yet possible');
    }

    public function getCreateDatabaseBuilder(): CreateDatabaseBuilderInterface
    {
        return $this->createDatabaseBuilder ??= new CreateDatabaseBuilder();
    }

    public function getCreateTableBuilder(): CreateTableBuilderInterface
    {
        return $this->createTableBuilder ??= new CreateTableBuilder($this);
    }

    public function getAlterTableBuilder(): AlterTableBuilderInterface
    {
        return $this->alterTableBuilder ??= new AlterTableBuilder($this);
    }
}
