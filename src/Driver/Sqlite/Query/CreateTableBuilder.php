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

use Access\Clause;
use Access\Driver\DriverInterface;
use Access\Driver\Query\CreateTableBuilderInterface;
use Access\Schema\Field;
use Access\Schema\Index;
use Access\Schema\Table;
use Access\Schema\Type;

/**
 * @see https://sqlite.org/lang_createtable.html
 * @author Tim <me@justim.net>
 * @internal
 */
class CreateTableBuilder implements CreateTableBuilderInterface
{
    private DriverInterface $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function primaryKey(Field $field): string
    {
        return '';
    }

    public function foreignKey(Field $field): string
    {
        $type = $field->getType();
        assert($type instanceof Type\Reference);

        return sprintf(
            'FOREIGN KEY (%s) REFERENCES %s (%s)',
            $this->driver->escapeIdentifier($field->getName()),
            $this->driver->escapeIdentifier($type->getTableName()),
            $this->driver->escapeIdentifier('id'),
        );
    }

    public function index(Index $index): string
    {
        if (!$index->isUnique()) {
            // should this blow up?
            return '';
        }

        $fields = array_map(
            fn(Clause\Field|string $field) => $this->driver->escapeIdentifier(
                $field instanceof Field ? $field->getName() : $field,
            ),
            $index->getFields(),
        );

        return sprintf('UNIQUE (%s)', implode(', ', $fields));
    }

    public function tableOptions(Table $table): string
    {
        return '';
    }
}
