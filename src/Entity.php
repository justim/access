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

namespace Access;

use Access\IdentifiableInterface;
use Access\Repository;
use BackedEnum;
use Psr\Clock\ClockInterface;
use Access\Schema\Field;
use Access\Schema\Table;
use Access\Schema\Type;

/**
 * Entity functionality
 *
 * @author Tim <me@justim.net>
 *
 * @psalm-type FieldOptions = array{
 *  default?: mixed,
 *  type?: self::FIELD_TYPE_*,
 *  enumName?: class-string<BackedEnum>,
 *  virtual?: bool,
 *  excludeInCopy?: bool,
 *  target?: class-string<Entity>,
 *  cascade?: Cascade,
 * }
 *
 * @psalm-type RelationOptions = array{
 *  target: class-string<Entity>,
 *  field: string,
 *  cascade?: Cascade,
 * }
 */
abstract class Entity implements IdentifiableInterface
{
    // list of supported field types
    protected const FIELD_TYPE_INT = 'int';
    protected const FIELD_TYPE_BOOL = 'bool';
    protected const FIELD_TYPE_DATETIME = 'datetime';
    protected const FIELD_TYPE_DATE = 'date';
    protected const FIELD_TYPE_JSON = 'json';
    protected const FIELD_TYPE_ENUM = 'enum';

    /**
     * Get the table name of the entity
     *
     * @return string
     */
    abstract public static function tableName(): string;

    /**
     * Get the field definitions
     *
     * @return array<string, mixed>
     * @psalm-return array<string, FieldOptions>
     */
    abstract public static function fields(): array;

    /**
     * @return array<string, mixed>
     * @psalm-return array<string, RelationOptions>
     */
    public static function relations(): array
    {
        return [];
    }

    /**
     * Does the entity have timestamps
     *
     * A `created_at` and `updated_at` date time field
     *
     * @return bool
     */
    public static function timestamps(): bool
    {
        return false;
    }

    /**
     * Does the entity have `created_at` field
     *
     * @return bool
     */
    public static function creatable(): bool
    {
        return false;
    }

    /**
     * Is the entity soft deletable
     *
     * A `deleted_at` must be present
     *
     * @return bool
     */
    public static function isSoftDeletable(): bool
    {
        return false;
    }

    public static function getTableSchema(): Table
    {
        return static::getGeneratedTableSchema();
    }

    /**
     * Generate a table schema from the (legacy) field definitions
     * @param array<string, FieldOptions>|null $fields
     */
    protected static function getGeneratedTableSchema(?array $fields = null): Table
    {
        // a best effort implementation to create a table schema,
        // should probably not be used to actually create a table.

        $table = new Table(
            static::tableName(),
            hasCreatedAt: static::timestamps() || static::creatable(),
            hasUpdatedAt: static::timestamps(),
            hasDeletedAt: static::isSoftDeletable(),
        );

        $fields ??= static::fields();

        foreach ($fields as $name => $field) {
            $type = null;

            if (isset($field['cascade']) && isset($field['target'])) {
                $type = new Type\Reference($field['target'], $field['cascade']);
            } elseif (isset($field['type'])) {
                if ($field['type'] === self::FIELD_TYPE_ENUM && isset($field['enumName'])) {
                    $type = new Type\Enum($field['enumName']);
                } else {
                    $type = match ($field['type']) {
                        self::FIELD_TYPE_INT => new Type\Integer(),
                        self::FIELD_TYPE_BOOL => new Type\Boolean(),
                        self::FIELD_TYPE_DATETIME => new Type\DateTime(),
                        self::FIELD_TYPE_DATE => new Type\Date(),
                        self::FIELD_TYPE_JSON => new Type\Json(),
                        default => null,
                    };
                }
            }

            /** @var array{string, ?Type, mixed} $initArgs */
            $initArgs = [$name, $type];

            if (array_key_exists('default', $field)) {
                /** @psalm-suppress MixedAssignement */
                $initArgs[2] = $field['default'];
            }

            $schemaField = new Field(...$initArgs);

            if (isset($field['virtual']) && $field['virtual'] === true) {
                $schemaField->markAsVirtual();
            }

            if (isset($field['excludeInCopy']) && $field['excludeInCopy'] === true) {
                $schemaField->setIncludeInCopy(false);
            }

            $table->addField($schemaField);
        }

        return $table;
    }

    /**
     * Get the repository class for entity
     *
     * @psalm-return class-string<Repository>
     *
     * @return string
     */
    public static function getRepository(): string
    {
        return Repository::class;
    }

    /**
     * Name of created at field
     * @deprecated use Table::CREATED_AT_FIELD
     */
    public const CREATED_AT_FIELD = Table::CREATED_AT_FIELD;

    /**
     * Name of updated at field
     * @deprecated use Table::UPDATED_AT_FIELD
     */
    public const UPDATED_AT_FIELD = Table::UPDATED_AT_FIELD;

    /**
     * Name of deleted at field
     * @deprecated use Table::DELETED_AT_FIELD
     */
    public const DELETED_AT_FIELD = Table::DELETED_AT_FIELD;

    /**
     * Date time format
     * @deprecated use Type\DateTime::DATABASE_FORMAT
     */
    public const DATETIME_FORMAT = Type\DateTime::DATABASE_FORMAT;

    /**
     * Date format
     * @deprecated use Type\Date::DATABASE_FORMAT
     */
    public const DATE_FORMAT = Type\Date::DATABASE_FORMAT;

    /**
     * ID of the entity
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * Data of in the entity
     *
     * @var array<string, mixed>
     */
    private array $values = [];

    /**
     * Diff for updating entities
     *
     * @var array<string, mixed>
     */
    private array $updatedFields = [];

    /**
     * Is the value available?
     *
     * @param string $field Field to check
     * @return bool
     */
    final protected function hasValue(string $field): bool
    {
        if ($field === 'id') {
            return $this->hasId();
        }

        return array_key_exists($field, $this->values);
    }

    /**
     * Get the value of a field
     *
     * @param string $field
     * @return mixed
     */
    final protected function get(string $field): mixed
    {
        if (!$this->hasValue($field)) {
            throw new Exception(sprintf('Field "%s" not available', $field));
        }

        if ($field === 'id') {
            return $this->getId();
        }

        return $this->values[$field];
    }

    /**
     * Set the value of a field
     *
     * @param string $field
     * @param IdentifiableInterface|mixed $value
     */
    final protected function set(string $field, mixed $value): void
    {
        if ($this->id !== null) {
            // value is the same, don't bother updating
            if (array_key_exists($field, $this->values) && $this->values[$field] === $value) {
                return;
            }

            if ($value instanceof IdentifiableInterface) {
                $this->updatedFields[$field] = $value->getId();
            } else {
                $this->updatedFields[$field] = $value;
            }
        }

        if ($field === 'id') {
            throw new Exception('Not possible to change ID');
        }

        if ($value instanceof IdentifiableInterface) {
            $this->values[$field] = $value->getId();
        } else {
            $this->values[$field] = $value;
        }
    }

    /**
     * Get the values behind this entity
     *
     * @return array<string, mixed>
     */
    final public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Get the ID of the entity
     *
     * Only available for entities also in the database
     *
     * @throws Exception When id is not yet set
     * @return int
     */
    final public function getId(): int
    {
        if ($this->id === null) {
            throw new Exception('ID not available yet');
        }

        return $this->id;
    }

    /**
     * Set the ID of the entity
     *
     * Should only be called from the Database class after an insert
     *
     * @throws Exception When id is already set
     * @param int $id
     */
    final public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new Exception('ID already set');
        }

        $this->id = $id;
    }

    /**
     * Has the entity a id set?
     *
     * @return bool
     */
    final public function hasId(): bool
    {
        return $this->id !== null;
    }

    /**
     * Has the entity changes
     *
     * Setting a field to the same value (===) will not change the output of this check
     *
     * @return bool
     */
    final public function hasChanges(): bool
    {
        return !empty($this->updatedFields);
    }

    /**
     * Get all the values needed to insert the entity
     *
     * @return array<string, mixed>
     */
    final public function getInsertValues(?ClockInterface $clock = null): array
    {
        $clock ??= new InternalClock();

        $table = $this->getResolvedTableSchema();
        $fields = $table->getFields();
        $values = [];

        foreach ($fields as $field) {
            if ($field->getIsVirtual()) {
                continue;
            } elseif (array_key_exists($field->getName(), $this->values)) {
                /** @var mixed $value */
                $value = $this->values[$field->getName()];
            } elseif ($field->hasDefault()) {
                $value = $field->getDefaultValue($this);
            } else {
                continue;
            }

            $values[$field->getName()] = $field->toDatabaseFormatValue($value);
        }

        $dateTimeType = new Type\DateTime();

        if ($table->hasCreatedAt()) {
            $values[Table::CREATED_AT_FIELD] = $dateTimeType->toDatabaseFormatValue($clock->now());
        }

        if ($table->hasUpdatedAt()) {
            $values[Table::UPDATED_AT_FIELD] = $dateTimeType->toDatabaseFormatValue($clock->now());
        }

        if ($table->hasDeletedAt() && !isset($this->values[Table::DELETED_AT_FIELD])) {
            $values[Table::DELETED_AT_FIELD] = null;
        }

        return $values;
    }

    /**
     * Get all updated field/values for entity
     *
     * @return array<string, mixed>
     */
    final public function getUpdateValues(?ClockInterface $clock = null): array
    {
        $clock ??= new InternalClock();

        /** @var array<string, mixed> $values */
        $values = [];

        $table = $this->getResolvedTableSchema();

        $dateTimeType = new Type\DateTime();

        foreach ($this->updatedFields as $fieldName => $value) {
            if ($fieldName === Table::DELETED_AT_FIELD) {
                $values[Table::DELETED_AT_FIELD] = $dateTimeType->toDatabaseFormatValue($value);

                continue;
            }

            $field = $table->getField($fieldName);
            if ($field !== null && $field->getIsVirtual()) {
                continue;
            }

            if ($field === null) {
                // field not in schema, just use raw value
                $values[$fieldName] = $value;

                continue;
            }

            $values[$fieldName] = $field->toDatabaseFormatValue($value);
        }

        if (!empty($values) && $table->hasUpdatedAt()) {
            $values[Table::UPDATED_AT_FIELD] = $dateTimeType->toDatabaseFormatValue($clock->now());
        }

        return $values;
    }

    /**
     * Mark the entity as updated
     *
     * The updated fields diff will be cleared and timestamps will be
     * filled
     *
     * @param array<string, mixed>|null $updatedFields
     */
    final public function markUpdated(?array $updatedFields = null): void
    {
        $this->updatedFields = [];

        if ($updatedFields !== null) {
            $table = $this->getResolvedTableSchema();
            $datetimeType = new Type\DateTime();

            /** @var mixed $value */
            foreach ($updatedFields as $fieldName => $value) {
                if ($table->isBuiltinDatetimeField($fieldName)) {
                    if ($value === null) {
                        $this->values[$fieldName] = null;
                    } else {
                        $this->values[$fieldName] = $datetimeType->fromDatabaseFormatValue($value);
                    }

                    continue;
                }

                if (!array_key_exists($fieldName, $this->values)) {
                    $field = $table->getField($fieldName);

                    if ($field === null) {
                        // field not in schema, just use raw value
                        $this->values[$fieldName] = $value;
                    } else {
                        $this->values[$fieldName] = $field->fromDatabaseFormatValue($value);
                    }
                }
            }
        }
    }

    /**
     * Fill all values
     *
     * @param array<string, mixed> $record
     */
    final public function hydrate(array $record): void
    {
        $table = $this->getResolvedTableSchema();
        $datetimeType = new Type\DateTime();

        /** @var mixed $value */
        foreach ($record as $fieldName => $value) {
            // the ID has special treatment
            if ($fieldName === 'id') {
                continue;
            }

            if ($table->isBuiltinDatetimeField($fieldName)) {
                if ($value === null) {
                    $this->values[$fieldName] = null;
                } else {
                    $this->values[$fieldName] = $datetimeType->fromDatabaseFormatValue($value);
                }

                continue;
            }

            $field = $table->getField($fieldName);

            if ($field === null) {
                // field not in schema, just use raw value
                $this->values[$fieldName] = $value;
            } else {
                $this->values[$fieldName] = $field->fromDatabaseFormatValue($value);
            }
        }

        if (isset($record['id'])) {
            $this->setId(intval($record['id']));
        }

        $this->markUpdated();
    }

    /**
     * Get the (resolved) table schema
     *
     * Defaults to `self::getTableSchema`
     *
     * @return Table
     */
    protected function getResolvedTableSchema(): Table
    {
        return static::getTableSchema();
    }

    /**
     * Make new entity instance with copied fields
     *
     * @return static
     */
    public function copy(): static
    {
        /**
         * @psalm-suppress UnsafeInstantiation
         * @phpstan-ignore new.static
         */
        $copy = new static();

        $record = $this->getValues();

        unset($record[Table::CREATED_AT_FIELD]);
        unset($record[Table::UPDATED_AT_FIELD]);
        unset($record[Table::DELETED_AT_FIELD]);

        $fields = $this->getResolvedTableSchema()->getFields();

        foreach ($fields as $field) {
            if (!$field->getIncludeInCopy()) {
                unset($record[$field->getName()]);
            }
        }

        $copy->values = $record;

        return $copy;
    }
}
