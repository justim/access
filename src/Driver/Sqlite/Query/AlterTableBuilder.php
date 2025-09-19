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

namespace Access\Driver\Sqlite\Query;

use Access\Clause\Field as ClauseField;
use Access\Driver\DriverInterface;
use Access\Driver\Query\AlterTableBuilderInterface;
use Access\Exception\NotSupportedException;
use Access\Schema\Field;
use Access\Schema\Index;
use Access\Schema\Table;

/**
 * @see https://sqlite.org/lang_altertable.html
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

    public function renameTable(Table|string $table): string
    {
        $table = $table instanceof Table ? $table->getName() : $table;

        return sprintf('RENAME TO %s', $this->driver->escapeIdentifier($table));
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
        // should this just be a no-op?
        throw new NotSupportedException('SQLite does not support changing fields');
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
        throw new NotSupportedException('SQLite does not support adding indexes in alter tables');
    }

    public function removeIndex(Index|string $index): string
    {
        throw new NotSupportedException('SQLite does not support removing indexes in alter tables');
    }

    public function renameIndex(Index|string $from, Index|string $to): string
    {
        throw new NotSupportedException('SQLite does not support renaming indexes in alter tables');
    }
}
