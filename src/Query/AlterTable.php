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

use Access\Clause;
use Access\Database;
use Access\Driver\DriverInterface;
use Access\Query;
use Access\Schema;
use Access\Schema\Index;
use Access\Schema\Table;
use Access\Schema\Type;

enum AlterType
{
    case RenameTable;
    case AddField;
    case RemoveField;
    case ChangeField;
    case ModifyField;
    case RenameField;
    case AddIndex;
    case RemoveIndex;
    case RenameIndex;
}

/**
 * Create ALTER TABLE query for given table
 *
 * @author Tim <me@justim.net>
 */
class AlterTable extends Query
{
    private Table $table;

    /**
     * @var array<array{
     *  type: AlterType,
     *  toTableName?: Table|string,
     *  fieldDefinition?: Schema\Field,
     *  fieldName?: Clause\Field,
     *  fromName?: Clause\Field,
     *  toName?: Clause\Field,
     *  toDefinition?: Schema\Field,
     *  indexName?: Index|string,
     *  indexDefinition?: Index,
     *  fromIndexName?: Index|string,
     *  toIndexName?: Index|string,
     * }>
     */
    private array $alters = [];

    /**
     * Create a ALTER TABLE query
     */
    public function __construct(Table $table)
    {
        $this->table = $table;

        parent::__construct($table, null);
    }

    public function renameTable(Table|string $toTableName): void
    {
        $this->alters[] = ['type' => AlterType::RenameTable, 'toTableName' => $toTableName];
    }

    public function addField(
        Clause\Field|string $field,
        ?Type $type = null,
        mixed $default = null,
    ): Schema\Field {
        /** @var array{string, ?Type, mixed} $args */
        $args = func_get_args();

        $field = $this->maybeCreateField(...$args);

        $this->alters[] = ['type' => AlterType::AddField, 'fieldDefinition' => $field];

        return $field;
    }

    public function removeField(Clause\Field|string $field): void
    {
        if (is_string($field)) {
            $field = new Clause\Field($field);
        }

        $this->alters[] = ['type' => AlterType::RemoveField, 'fieldName' => $field];
    }

    public function changeField(
        Clause\Field|string $from,
        Schema\Field|string $to,
        ?Type $type = null,
        mixed $default = null,
    ): Schema\Field {
        if (is_string($from)) {
            $from = new Clause\Field($from);
        }

        /** @var array{Clause\Field|string, string, ?Type, mixed} $args */
        $args = func_get_args();
        array_shift($args); // remove $from

        $to = $this->maybeCreateField(...$args);

        $this->alters[] = [
            'type' => AlterType::ChangeField,
            'fromName' => $from,
            'toDefinition' => $to,
        ];

        return $to;
    }

    public function modifyField(
        Schema\Field|string $to,
        ?Type $type = null,
        mixed $default = null,
    ): Schema\Field {
        /** @var array{Schema\Field|string, ?Type, mixed} $args */
        $args = func_get_args();

        $to = $this->maybeCreateField(...$args);

        $this->alters[] = [
            'type' => AlterType::ModifyField,
            'toDefinition' => $to,
        ];

        return $to;
    }

    public function renameField(Clause\Field|string $from, Clause\Field|string $to): void
    {
        if (is_string($from)) {
            $from = new Clause\Field($from);
        }

        if (is_string($to)) {
            $to = new Clause\Field($to);
        }

        $this->alters[] = ['type' => AlterType::RenameField, 'fromName' => $from, 'toName' => $to];
    }

    /**
     * @param array<string|Clause\Field>|string|Clause\Field|null $fields
     * @return Index The new index definition
     */
    public function addIndex(
        Index|string $index,
        array|string|Clause\Field|null $fields = null,
    ): Index {
        if (is_string($index)) {
            if ($fields === null) {
                throw new \InvalidArgumentException(
                    'Fields must be provided when index name is given as string',
                );
            }

            $index = $this->table->index($index, $fields);
        }

        $this->alters[] = ['type' => AlterType::AddIndex, 'indexDefinition' => $index];

        return $index;
    }

    public function removeIndex(Index|string $index): void
    {
        $this->alters[] = ['type' => AlterType::RemoveIndex, 'indexName' => $index];
    }

    public function renameIndex(Index|string $from, Index|string $to): void
    {
        $this->alters[] = [
            'type' => AlterType::RenameIndex,
            'fromIndexName' => $from,
            'toIndexName' => $to,
        ];
    }

    /**
     * Mayeb create field instance if string is given
     *
     * @param Type|null $type Defaults to VarChar if null
     */
    private function maybeCreateField(
        Schema\Field|string $field,
        ?Type $type = null,
        mixed $default = null,
    ): Schema\Field {
        if ($field instanceof Schema\Field) {
            return $field;
        }

        // make sure the dont' trip up our logic with the default value,
        // if it's not provided by the user, dont't pass a third argument to the constructor
        /** @var array{string, ?Type, mixed} $args */
        $args = func_get_args();

        $field = $this->table->field(...$args);

        return $field;
    }

    public function getSql(?DriverInterface $driver = null): ?string
    {
        $driver = Database::getDriverOrDefault($driver);
        $builder = $driver->getAlterTableBuilder();

        $columnDefinitions = [];

        foreach ($this->alters as $alter) {
            switch ($alter['type']) {
                case AlterType::RenameTable:
                    assert(
                        isset($alter['toTableName']),
                        'To table name must be set for RenameTable alter type',
                    );

                    $columnDefinitions[] = $builder->renameTable($alter['toTableName']);
                    break;

                case AlterType::AddField:
                    assert(
                        isset($alter['fieldDefinition']),
                        'Field must be set for AddField alter type',
                    );

                    $columnDefinitions[] = $builder->addField($alter['fieldDefinition']);
                    break;

                case AlterType::RemoveField:
                    assert(
                        isset($alter['fieldName']),
                        'Field must be set for RemoveField alter type',
                    );

                    $columnDefinitions[] = $builder->removeField($alter['fieldName']);
                    break;

                case AlterType::ChangeField:
                    assert(
                        isset($alter['fromName']) && isset($alter['toDefinition']),
                        'From and To fields must be set for ChangeField alter type',
                    );

                    $columnDefinitions[] = $builder->changeField(
                        $alter['fromName'],
                        $alter['toDefinition'],
                    );
                    break;

                case AlterType::ModifyField:
                    assert(
                        isset($alter['toDefinition']),
                        'To fields must be set for ModifyField alter type',
                    );

                    $columnDefinitions[] = $builder->modifyField($alter['toDefinition']);
                    break;

                case AlterType::RenameField:
                    assert(
                        isset($alter['fromName']) && isset($alter['toName']),
                        'From and To fields must be set for RenameField alter type',
                    );

                    $columnDefinitions[] = $builder->renameField(
                        $alter['fromName'],
                        $alter['toName'],
                    );
                    break;

                case AlterType::AddIndex:
                    assert(
                        isset($alter['indexDefinition']),
                        'Index must be set for AddIndex alter type',
                    );

                    $columnDefinitions[] = $builder->addIndex($alter['indexDefinition']);
                    break;

                case AlterType::RemoveIndex:
                    assert(
                        isset($alter['indexName']),
                        'Index must be set for RemoveIndex alter type',
                    );

                    $columnDefinitions[] = $builder->removeIndex($alter['indexName']);
                    break;

                case AlterType::RenameIndex:
                    assert(
                        isset($alter['fromIndexName']) && isset($alter['toIndexName']),
                        'From and To indexes must be set for RenameIndex alter type',
                    );

                    $columnDefinitions[] = $builder->renameIndex(
                        $alter['fromIndexName'],
                        $alter['toIndexName'],
                    );
                    break;
            }
        }

        if (count($columnDefinitions) === 0) {
            return null;
        }

        $sql = sprintf(
            'ALTER TABLE %s %s',
            $driver->escapeIdentifier($this->tableName),
            implode(', ', $columnDefinitions),
        );

        return $sql;
    }
}
