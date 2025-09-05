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

use Access\Driver\DriverInterface;
use Access\Driver\Query\CreateTableBuilderInterface;
use Access\Schema\Field;
use Access\Schema\Index;
use Access\Schema\Type;

/**
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
        $type = $field->getType();
        assert($type instanceof Type\Integer);

        return sprintf('PRIMARY KEY (%s)', $this->driver->escapeIdentifier($field->getName()));
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
        return $index->getSqlDefinition($this->driver);
    }
}
