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

namespace Access\Driver\Mysql;

use Access\Clause\Field;
use Access\Driver\Driver;
use Access\Driver\Mysql\MysqlSqlTypeDefinitionBuilder;
use Access\Driver\Mysql\Query\AlterTableBuilder;
use Access\Driver\Mysql\Query\CreateDatabaseBuilder;
use Access\Driver\Mysql\Query\CreateTableBuilder;
use Access\Driver\Query\AlterTableBuilderInterface;
use Access\Driver\Query\CreateDatabaseBuilderInterface;
use Access\Driver\Query\CreateTableBuilderInterface;
use Access\Driver\SqlTypeDefinitionBuilderInterface;
use Access\Exception;
use Access\Exception\ConnectionGoneException;
use Access\Exception\LockNotAcquiredException;
use Access\Exception\TableDoesNotExistException;
use Access\ReadLock;
use Access\Schema\Index;

/**
 * MySQL specific driver
 *
 * @author Tim <me@justim.net>
 * @internal
 * @psalm-suppress MissingConstructor
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress RedundantPropertyInitializationCheck
 */
class Mysql extends Driver
{
    public const NAME = 'mysql';

    private const ERROR_CODE_NO_SUCH_TABLE = 1146;
    private const ERROR_CODE_BAD_TABLE_ERROR = 1051;
    private const ERROR_CODE_SERVER_GONE_ERROR = 2006;
    private const ERROR_CODE_CLIENT_INTERACTION_TIMEOUT = 4031;

    private const ERROR_CODE_LOCK_NOWAIT = 3572;
    private const ERROR_CODE_LOCK_WAIT_TIMEOUT = 1205;

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

        return str_replace('.', '`.`', sprintf('`%s`', str_replace('`', '``', $identifier)));
    }

    /**
     * Get a debug string value for a value in MySQL dialect
     *
     * Useful for the debug query, should not be used otherwise, use prepared statements
     */
    public function getDebugStringValue(mixed $value): string
    {
        return sprintf('"%s"', addslashes((string) $value));
    }

    /**
     * Convert a PDOException to a more specific Exception
     *
     * @see https://dev.mysql.com/doc/mysql-errors/8.0/en/server-error-reference.html
     * @see https://dev.mysql.com/doc/mysql-errors/8.0/en/client-error-reference.html
     */
    public function convertPdoException(\PDOException $e): ?Exception
    {
        $message = $e->getMessage();

        if ($e->errorInfo !== null) {
            [, $code] = $e->errorInfo;
        } else {
            $code = $e->getCode();
        }

        switch ($code) {
            case self::ERROR_CODE_NO_SUCH_TABLE:
            case self::ERROR_CODE_BAD_TABLE_ERROR:
                return new TableDoesNotExistException(
                    sprintf('Table does not exists: %s', $message),
                    0,
                    $e,
                );

            case self::ERROR_CODE_SERVER_GONE_ERROR:
            case self::ERROR_CODE_CLIENT_INTERACTION_TIMEOUT:
                return new ConnectionGoneException(
                    sprintf('Database server has gone away: %s', $message),
                    0,
                    $e,
                );

            case self::ERROR_CODE_LOCK_NOWAIT:
                return new LockNotAcquiredException(
                    sprintf('Unable to acquire lock immediately: %s', $message),
                    0,
                    $e,
                );

            case self::ERROR_CODE_LOCK_WAIT_TIMEOUT:
                return new LockNotAcquiredException(
                    sprintf('Unable to acquire lock in time: %s', $message),
                    0,
                    $e,
                );

            default:
                return null;
        }
    }

    /**
     * Get the function name for random in MySQL dialect
     */
    public function getFunctionNameRandom(): string
    {
        return 'RAND()';
    }

    /**
     * Has the MySQL driver support for LOCK/UNLOCK TABLES?
     */
    public function hasLockSupport(): bool
    {
        return true;
    }

    /**
     * Get the SQL for a read lock
     */
    public function getReadLockSql(ReadLock $readLock): string
    {
        return match ($readLock) {
            ReadLock::Share => 'FOR SHARE',
            ReadLock::ShareNoWait => 'FOR SHARE NOWAIT',
            ReadLock::Update => 'FOR UPDATE',
            ReadLock::UpdateNoWait => 'FOR UPDATE NOWAIT',
        };
    }

    public function getSqlTypeDefinitionBuilder(): SqlTypeDefinitionBuilderInterface
    {
        return $this->sqlTypeDefinition ??= new MysqlSqlTypeDefinitionBuilder($this);
    }

    public function getSqlIndexDefinition(Index $index): string
    {
        $fields = array_map(
            fn(Field|string $field): string => $this->escapeIdentifier(
                $field instanceof Field ? $field->getName() : $field,
            ),
            $index->getFields(),
        );

        return sprintf(
            '%sINDEX %s (%s)',
            $index->isUnique() ? 'UNIQUE ' : '',
            $this->escapeIdentifier($index->getName()),
            implode(', ', $fields),
        );
    }

    public function getCreateDatabaseBuilder(): CreateDatabaseBuilderInterface
    {
        return $this->createDatabaseBuilder ??= new CreateDatabaseBuilder($this);
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
