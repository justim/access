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

namespace Access\Driver\Mysql\Query;

use Access\Clause\Field as ClauseField;
use Access\Driver\DriverInterface;
use Access\Driver\Query\AlterTableBuilderInterface;
use Access\Schema\Field;
use Access\Schema\Index;

/**
 * @author Tim <me@justim.net>
 * @internal
 */
class AlterTableBuilder implements AlterTableBuilderInterface
{
    private DriverInterface $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function addField(Field $field): string
    {
        return sprintf('ADD COLUMN %s', $field->getSqlDefinition($this->driver));
    }

    public function removeField(ClauseField $field): string
    {
        return sprintf('DROP COLUMN %s', $this->driver->escapeIdentifier($field));
    }

    public function changeField(ClauseField $from, Field $to): string
    {
        return sprintf(
            'CHANGE COLUMN %s %s',
            $this->driver->escapeIdentifier($from),
            $to->getSqlDefinition($this->driver),
        );
    }

    public function renameField(ClauseField $from, ClauseField $to): string
    {
        return sprintf(
            'RENAME COLUMN %s TO %s',
            $this->driver->escapeIdentifier($from),
            $this->driver->escapeIdentifier($to),
        );
    }

    public function addIndex(Index $index): string
    {
        $definition = $index->getSqlDefinition($this->driver);
        return sprintf('ADD %s', $definition);
    }

    public function removeIndex(Index|string $index): string
    {
        $indexName = $index instanceof Index ? $index->getName() : $index;

        return sprintf('DROP INDEX %s', $this->driver->escapeIdentifier($indexName));
    }

    public function renameIndex(Index|string $from, Index|string $to): string
    {
        $from = $from instanceof Index ? $from->getName() : $from;
        $to = $to instanceof Index ? $to->getName() : $to;

        return sprintf(
            'RENAME INDEX %s TO %s',
            $this->driver->escapeIdentifier($from),
            $this->driver->escapeIdentifier($to),
        );
    }
}
