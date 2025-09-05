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
use Access\Driver\Mysql\Query\CreateTableBuilder;
use Access\Driver\Query\AlterTableBuilderInterface;
use Access\Driver\Query\CreateTableBuilderInterface;
use Access\Driver\SqlTypeDefinitionBuilderInterface;
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

    private SqlTypeDefinitionBuilderInterface $sqlTypeDefinition;
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

    public function getCreateTableBuilder(): CreateTableBuilderInterface
    {
        return $this->createTableBuilder ??= new CreateTableBuilder($this);
    }

    public function getAlterTableBuilder(): AlterTableBuilderInterface
    {
        return $this->alterTableBuilder ??= new AlterTableBuilder($this);
    }
}
