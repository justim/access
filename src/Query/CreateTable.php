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
use Access\Query;
use Access\Schema\Field;
use Access\Schema\Table;
use Access\Schema\Type;

/**
 * Create CREATE TABLE query for given table
 *
 * @author Tim <me@justim.net>
 */
class CreateTable extends Query
{
    private Table $table;

    /**
     * Create a CREATE TABLE query
     */
    public function __construct(Table $table)
    {
        parent::__construct($table, null);

        $this->table = $table;
    }

    public function getSql(?DriverInterface $driver = null): string
    {
        $driver = Database::getDriverOrDefault($driver);
        $builder = $driver->getCreateTableBuilder();

        $tableParts = [];
        $fields = [$this->idField(), ...$this->table->getFields()];

        if ($this->table->hasCreatedAt()) {
            $fields[] = $this->createdAtField();
        }

        if ($this->table->hasUpdatedAt()) {
            $fields[] = $this->updatedAtField();
        }

        if ($this->table->hasDeletedAt()) {
            $fields[] = $this->deletedAtField();
        }

        foreach ($fields as $field) {
            if ($field->getIsVirtual()) {
                continue;
            }

            $tableParts[] = $field->getSqlDefinition($driver);
        }

        foreach ($fields as $field) {
            if ($field->getIsVirtual()) {
                continue;
            }

            if ($field->isPrimaryKey()) {
                $tableParts[] = $builder->primaryKey($field);
            }

            if ($field->getType() instanceof Type\Reference) {
                $tableParts[] = $builder->foreignKey($field);
            }
        }

        foreach ($this->table->getIndexes() as $index) {
            $tableParts[] = $builder->index($index);
        }

        $tableParts = array_filter($tableParts);

        $sql = sprintf(
            "CREATE TABLE %s (\n    %s\n)",
            $driver->escapeIdentifier($this->tableName),
            implode(",\n    ", $tableParts),
        );

        return $sql;
    }

    private function idField(): Field
    {
        $idField = new Field('id', new Type\Integer());
        $idField->markAsPrimaryKey();
        $idField->markAsAutoIncrement();

        return $idField;
    }

    private function createdAtField(): Field
    {
        return new Field(Table::CREATED_AT_FIELD, new Type\DateTime());
    }

    private function updatedAtField(): Field
    {
        return new Field(Table::UPDATED_AT_FIELD, new Type\DateTime());
    }

    private function deletedAtField(): Field
    {
        return new Field(Table::DELETED_AT_FIELD, new Type\DateTime(), null);
    }
}
