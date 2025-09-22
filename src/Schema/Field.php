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

use Access\Clause\Field as ClauseField;
use Access\Driver\DriverInterface;
use Access\Entity;
use Access\Schema\Exception\NoDefaultValueException;
use Access\Schema\Type\VarChar;
use BackedEnum;

class Field extends ClauseField
{
    /**
     * Name of the field
     */
    private string $name;

    /**
     * Type of the field
     *
     * Will handle conversion to/from database format
     */
    private Type $type;

    /**
     * Default value of the field
     *
     * Can be a static value or a callable that returns the default value
     * The callable will receive the entity as first argument
     */
    private mixed $default;

    /**
     * Does the field have a default value?
     */
    // setting the `$default` field to `null` is not the same, because `null` is a valid value
    private bool $hasDefault;

    /**
     * Is the field nullable?
     *
     * If a field has a default value of `null`, it is automatically nullable
     */
    private bool $nullable = false;

    /**
     * Is the field virtual (not stored in the database)?
     */
    private bool $isVirtual = false;

    /**
     * Should the field be included when copying an entity?
     */
    private bool $includeInCopy = true;

    /**
     * Is the field a primary key?
     */
    private bool $isPrimaryKey = false;

    /**
     * Does the field auto-increment?
     */
    private bool $hasAutoIncrement = false;

    /**
     * After which field should this field appear
     *
     * Migrations only
     */
    private ClauseField|string|null $after = null;

    /**
     * @param Type|null $type Defaults to `VarChar` if null
     * @param mixed $default Not providing it means no default, providing null means default null
     */
    public function __construct(string $name, ?Type $type = null, mixed $default = null)
    {
        $this->name = $name;
        $this->type = $type ?? new VarChar();

        // has the default value been provided by the user, if yes, then the field has a default value
        $hasDefault = func_num_args() > 2;

        if ($hasDefault) {
            $this->default = $default;
            $this->hasDefault = true;

            if ($default === null) {
                $this->nullable = true;
            }
        } else {
            $this->default = null;
            $this->hasDefault = false;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * Override the default value of the field
     *
     * The field will have a default value
     *
     * @param mixed $default
     */
    public function setDefault(mixed $default): void
    {
        $this->default = $default;
        $this->hasDefault = true;
        $this->nullable = $default === null;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    /**
     * Get the default value for the field
     *
     * The entity is passed to the callable default value
     *
     * @return mixed
     */
    public function getDefaultValue(Entity $entity)
    {
        if (!$this->hasDefault()) {
            throw new NoDefaultValueException(
                sprintf('No default value for field "%s"', $this->name),
            );
        }

        if (is_callable($this->default)) {
            return call_user_func($this->default, $entity);
        } else {
            return $this->default;
        }
    }

    /**
     * @psalm-assert int|float|string|bool|BackedEnum|null $this->default
     */
    public function hasStaticDefault(): bool
    {
        return $this->hasDefault() &&
            (is_scalar($this->default) ||
                $this->default instanceof BackedEnum ||
                $this->default === null);
    }

    public function getStaticDefaultValue(): int|float|string|bool|BackedEnum|null
    {
        if (!$this->hasStaticDefault()) {
            throw new NoDefaultValueException(
                sprintf('No static default value for field "%s"', $this->name),
            );
        }

        return $this->default;
    }

    /**
     * Override whether the field is nullable
     *
     * On creation of the field this field is automatically set to nullable if the
     * default value is null. Setting a default value will override this again.
     */
    public function markAsNullable(bool $nullable = true): void
    {
        $this->nullable = $nullable;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function markAsPrimaryKey(bool $primaryKey = true): void
    {
        $this->isPrimaryKey = $primaryKey;
    }

    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    public function markAsAutoIncrement(bool $autoIncrement = true): void
    {
        $this->hasAutoIncrement = $autoIncrement;
    }

    public function hasAutoIncrement(): bool
    {
        return $this->hasAutoIncrement;
    }

    public function setIsVirtual(bool $isVirtual): void
    {
        $this->isVirtual = $isVirtual;
    }

    public function getIsVirtual(): bool
    {
        return $this->isVirtual;
    }

    public function markAsVirtual(): void
    {
        $this->setIsVirtual(true);
    }

    public function setIncludeInCopy(bool $includeInCopy): void
    {
        $this->includeInCopy = $includeInCopy;
    }

    public function getIncludeInCopy(): bool
    {
        return $this->includeInCopy;
    }

    public function after(ClauseField|string|null $field): void
    {
        $this->after = $field;
    }

    public function getAfter(): ClauseField|string|null
    {
        return $this->after;
    }

    /**
     * Convert a value from database format to PHP format
     */
    public function fromDatabaseFormatValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->type->fromDatabaseFormatValue($value);
    }

    /**
     * Convert a value from PHP format to database format
     */
    public function toDatabaseFormatValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->type->toDatabaseFormatValue($value);
    }

    /**
     * Get the SQL definition of the field
     *
     * Contains the name and the type definition
     */
    public function getSqlDefinition(DriverInterface $driver): string
    {
        return sprintf(
            '%s %s',
            $driver->escapeIdentifier($this->getName()),
            $driver->getSqlFieldDefinition($this),
        );
    }
}
