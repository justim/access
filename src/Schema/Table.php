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

namespace Access\Schema;

use Access\Clause;
use Access\Schema\Type;

class Table
{
    /**
     * Name of created at field
     */
    public const CREATED_AT_FIELD = 'created_at';

    /**
     * Name of updated at field
     */
    public const UPDATED_AT_FIELD = 'updated_at';

    /**
     * Name of deleted at field
     */
    public const DELETED_AT_FIELD = 'deleted_at';

    private string $name;

    /**
     * @var Field[] $fields
     */
    private array $fields = [];

    /**
     * @var Index[] $indexes
     */
    private array $indexes = [];

    private bool $hasCreatedAt = false;
    private bool $hasUpdatedAt = false;
    private bool $hasDeletedAt = false;

    private Charset $defautCharset = Charset::Utf8;
    private Collate $collate = Collate::Default;
    private Engine $engine = Engine::Default;

    public function __construct(
        string $name,
        bool $hasCreatedAt = false,
        bool $hasUpdatedAt = false,
        bool $hasDeletedAt = false,
    ) {
        $this->name = $name;
        $this->hasCreatedAt = $hasCreatedAt;
        $this->hasUpdatedAt = $hasUpdatedAt;
        $this->hasDeletedAt = $hasDeletedAt;
    }

    /**
     * Create and add a field to the table.
     *
     * @param Type|null $type Defaults to VarChar if null
     */
    public function field(string $name, ?Type $type = null, mixed $default = null): Field
    {
        // make sure the dont' trip up our logic with the default value,
        // if it's not provided by the user, dont't pass a third argument to the constructor
        /** @var array{string, ?Type, mixed} $args */
        $args = func_get_args();

        $field = new Field(...$args);

        $this->addField($field);

        return $field;
    }

    /**
     * @param array<string|Clause\Field>|string|Clause\Field $fields
     */
    public function index(string $name, array|string|Clause\Field $fields): Index
    {
        $index = new Index($name, $fields);

        $this->addIndex($index);

        return $index;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addField(Field $field): void
    {
        $this->fields[] = $field;
    }

    public function addIndex(Index $index): void
    {
        $this->indexes[] = $index;
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get the names of all (defined) fields in the table
     *
     * @return string[]
     */
    public function getFieldNames(): array
    {
        return array_map(fn(Field $field): string => $field->getName(), $this->fields);
    }

    /**
     * Does the table have a field with the given name?
     */
    public function hasField(string $name): bool
    {
        return $this->getField($name) !== null;
    }

    /**
     * Get a field by its name
     */
    public function getField(string $name): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return Index[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function hasCreatedAt(): bool
    {
        return $this->hasCreatedAt;
    }

    public function hasUpdatedAt(): bool
    {
        return $this->hasUpdatedAt;
    }

    public function hasDeletedAt(): bool
    {
        return $this->hasDeletedAt;
    }

    /**
     * Is the given field a built-in date time field
     *
     * Checks if the feature for those fields is enabled and the predetermined names
     *
     * @param string $field The field name
     * @return bool Is a built-in date time field
     */
    public function isBuiltinDatetimeField(string $field): bool
    {
        if ($this->hasCreatedAt() && $field === self::CREATED_AT_FIELD) {
            return true;
        }

        if ($this->hasUpdatedAt() && $field === self::UPDATED_AT_FIELD) {
            return true;
        }

        if ($this->hasDeletedAt() && $field === self::DELETED_AT_FIELD) {
            return true;
        }

        return false;
    }

    public function getDefaultCharset(): Charset
    {
        return $this->defautCharset;
    }

    public function setDefaultCharset(Charset $charset): void
    {
        $this->defautCharset = $charset;
    }

    public function getCollate(): Collate
    {
        return $this->collate;
    }

    public function setCollate(Collate $collate): void
    {
        $this->collate = $collate;
    }

    public function getEngine(): Engine
    {
        return $this->engine;
    }

    public function setEngine(Engine $engine): void
    {
        $this->engine = $engine;
    }
}
