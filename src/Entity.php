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

/**
 * Entity functionality
 *
 * @author Tim <me@justim.net>
 */
abstract class Entity implements IdentifiableInterface
{
    // list of supported field types
    protected const FIELD_TYPE_INT = 'int';
    protected const FIELD_TYPE_BOOL = 'bool';
    protected const FIELD_TYPE_DATETIME = 'datetime';
    protected const FIELD_TYPE_DATE = 'date';
    protected const FIELD_TYPE_JSON = 'json';

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
     * @psalm-return array<string, array{default?: mixed, type?: string, virtual?: bool, excludeInCopy?: bool}>
     */
    abstract public static function fields(): array;

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

    /**
     * Date time format
     */
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Date format
     */
    public const DATE_FORMAT = 'Y-m-d';

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
     * Get the value of a field
     *
     * @param string $field
     * @return mixed
     */
    final protected function get(string $field)
    {
        if (!array_key_exists($field, $this->values)) {
            throw new Exception(sprintf('Field "%s" not available', $field));
        }

        return $this->values[$field];
    }

    /**
     * Set the value of a field
     *
     * @param string $field
     * @param IdentifiableInterface|mixed $value
     */
    final protected function set(string $field, $value): void
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
     * @return array
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
    final public function getInsertValues(): array
    {
        $values = [];
        $fields = $this->getResolvedFields();

        foreach ($fields as $field => $options) {
            if (isset($options['virtual']) && $options['virtual'] === true) {
                continue;
            } elseif (array_key_exists($field, $this->values)) {
                /** @var mixed $value */
                $value = $this->values[$field];
            } elseif (array_key_exists('default', $options)) {
                if (is_callable($options['default'])) {
                    $value = call_user_func($options['default'], $this);
                } else {
                    $value = $options['default'];
                }
            } else {
                continue;
            }

            $values[$field] = $this->toDatabaseFormat($field, $value);
        }

        if (static::timestamps()) {
            $values[self::CREATED_AT_FIELD] = $this->toDatabaseFormatValue(
                self::FIELD_TYPE_DATETIME,
                new \DateTimeImmutable(),
            );

            $values[self::UPDATED_AT_FIELD] = $this->toDatabaseFormatValue(
                self::FIELD_TYPE_DATETIME,
                new \DateTimeImmutable(),
            );
        }

        if (static::isSoftDeletable() && !isset($values[self::DELETED_AT_FIELD])) {
            $values[self::DELETED_AT_FIELD] = null;
        }

        return $values;
    }

    /**
     * Get all updated field/values for entity
     *
     * @return array<string, mixed>
     */
    final public function getUpdateValues(): array
    {
        /** @var array<string, mixed> $values */
        $values = [];
        $fields = $this->getResolvedFields();

        foreach ($this->updatedFields as $field => $value) {
            if ($field === self::DELETED_AT_FIELD) {
                $values[self::DELETED_AT_FIELD] = $this->toDatabaseFormatValue(
                    self::FIELD_TYPE_DATETIME,
                    $value,
                );

                continue;
            }

            if (isset($fields[$field])) {
                $options = $fields[$field];

                if (isset($options['virtual']) && $options['virtual'] === true) {
                    continue;
                }
            }

            $values[(string) $field] = $this->toDatabaseFormat((string) $field, $value);
        }

        if (!empty($values) && static::timestamps()) {
            $values[self::UPDATED_AT_FIELD] = $this->toDatabaseFormatValue(
                self::FIELD_TYPE_DATETIME,
                new \DateTimeImmutable(),
            );
        }

        return $values;
    }

    /**
     * Mark the entity as updated
     *
     * The updated fields diff will be cleared and timestamps will be
     * filled
     *
     * @param array $updatedFields
     */
    final public function markUpdated(array $updatedFields = null): void
    {
        $this->updatedFields = [];

        if ($updatedFields !== null) {
            foreach ($updatedFields as $field => $value) {
                if ($this->isBuiltinDatetimeField($field)) {
                    $this->values[$field] = $this->fromDatabaseFormatValue(
                        self::FIELD_TYPE_DATETIME,
                        $value,
                    );

                    continue;
                }

                if (!array_key_exists($field, $this->values)) {
                    $this->values[(string) $field] = $this->fromDatabaseFormat(
                        (string) $field,
                        $value,
                    );
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
        foreach ($record as $field => $value) {
            // the ID has special treatment
            if ($field === 'id') {
                continue;
            }

            if ($this->isBuiltinDatetimeField($field)) {
                $this->values[$field] = $this->fromDatabaseFormatValue(
                    self::FIELD_TYPE_DATETIME,
                    $value,
                );

                continue;
            }

            $this->values[(string) $field] = $this->fromDatabaseFormat((string) $field, $value);
        }

        if (isset($record['id'])) {
            $this->setId(intval($record['id']));
        }

        $this->markUpdated();
    }

    /**
     * Get a value for a field in the database format
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    private function toDatabaseFormat(string $field, $value)
    {
        $fields = $this->getResolvedFields();

        if (isset($fields[$field])) {
            $options = $fields[$field];

            if (!isset($options['type'])) {
                return $value;
            }

            $value = $this->toDatabaseFormatValue($options['type'], $value);
        }

        return $value;
    }

    /**
     * Get a value for a type in the database format
     *
     * @param string $type
     * @param mixed $value
     * @return mixed
     */
    private function toDatabaseFormatValue(string $type, $value)
    {
        if ($value === null) {
            return $value;
        }

        switch ($type) {
            case self::FIELD_TYPE_BOOL:
                return intval($value);

            case self::FIELD_TYPE_DATETIME:
                /** @var \DateTimeInterface $value */
                return $this->fromMutable($value)
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format(self::DATETIME_FORMAT);

            case self::FIELD_TYPE_DATE:
                /** @var \DateTimeInterface $value */
                return $this->fromMutable($value)
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format(self::DATE_FORMAT);

            case self::FIELD_TYPE_JSON:
                return json_encode($value);

            default:
                return $value;
        }
    }

    /**
     * Get a value for a field as a PHP value
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    private function fromDatabaseFormat(string $field, $value)
    {
        $fields = $this->getResolvedFields();

        if (isset($fields[$field])) {
            $options = $fields[$field];

            if (!isset($options['type'])) {
                return $value;
            }

            $value = $this->fromDatabaseFormatValue($options['type'], $value);
        }

        return $value;
    }

    /**
     * Get a value for a type as a PHP value
     *
     * @param string $type
     * @param mixed $value
     * @return mixed
     */
    private function fromDatabaseFormatValue(string $type, $value)
    {
        if ($value === null) {
            return $value;
        }

        switch ($type) {
            case self::FIELD_TYPE_INT:
                return intval($value);

            case self::FIELD_TYPE_BOOL:
                return boolval($value);

            case self::FIELD_TYPE_DATETIME:
                if (!is_string($value)) {
                    throw new Exception('Invalid datetime value');
                }

                return \DateTimeImmutable::createFromFormat(
                    self::DATETIME_FORMAT,
                    $value,
                    new \DateTimeZone('UTC'),
                );

            case self::FIELD_TYPE_DATE:
                if (!is_string($value)) {
                    throw new Exception('Invalid date value');
                }

                return \DateTimeImmutable::createFromFormat(
                    self::DATE_FORMAT,
                    $value,
                    new \DateTimeZone('UTC'),
                );

            case self::FIELD_TYPE_JSON:
                if (!is_string($value)) {
                    throw new Exception('Invalid json value');
                }

                return json_decode($value, true);

            default:
                return $value;
        }
    }

    /**
     * Is the given field a built-in date time field
     *
     * Checks if the feature for those fields is enabled and the predetermined names
     *
     * @param string $field The field name
     * @return bool Is a built-in date time field
     */
    private function isBuiltinDatetimeField(string $field): bool
    {
        if (
            static::timestamps() &&
            ($field === self::CREATED_AT_FIELD || $field === self::UPDATED_AT_FIELD)
        ) {
            return true;
        }

        if (static::isSoftDeletable() && $field === self::DELETED_AT_FIELD) {
            return true;
        }

        return false;
    }

    /**
     * Make mutable date immutable, if needed
     *
     * @param \DateTimeInterface $date
     * @return \DateTimeImmutable
     */
    private function fromMutable(\DateTimeInterface $date): \DateTimeImmutable
    {
        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }

        return new \DateTimeImmutable($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }

    /**
     * Get the (resolved) field definitions
     *
     * Defaults to `self::fields`
     *
     * @return array<string, mixed>
     * @psalm-return array<string, array{default?: mixed, type?: string, virtual?: bool, excludeInCopy?: bool}>
     */
    protected function getResolvedFields(): array
    {
        return static::fields();
    }

    /**
     * Make new entity instance with copied fields
     *
     * @return static
     */
    public function copy(): Entity
    {
        $copy = new static();

        $record = $this->getValues();

        unset($record[self::CREATED_AT_FIELD]);
        unset($record[self::UPDATED_AT_FIELD]);
        unset($record[self::DELETED_AT_FIELD]);

        $fields = $this->getResolvedFields();

        foreach ($fields as $field => $options) {
            if (isset($options['excludeInCopy']) && $options['excludeInCopy']) {
                unset($record[$field]);
            }
        }

        $copy->values = $record;

        return $copy;
    }
}
