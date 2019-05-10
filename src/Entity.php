<?php

declare(strict_types=1);

namespace Access;

use Access\Repository;

/**
 * Entity functionality
 */
abstract class Entity
{
    /**
     * Get the table name of the entity
     *
     * @return string
     */
    abstract public static function tableName(): string;

    /**
     * Get the field definitions
     *
     * @return array
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

    public static function getRepository(): string
    {
        return Repository::class;
    }

    /**
     * Name of created at field
     */
    private const CREATED_AT_FIELD = 'created_at';

    /**
     * Name of updated at field
     */
    private const UPDATED_AT_FIELD = 'updated_at';

    /**
     * Date time format
     */
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * ID of the entity
     *
     * @var int|null
     */
    private $id = null;

    /**
     * Data of in the entity
     *
     * @var mixed[]
     */
    private $values = [];

    /**
     * Diff for updating entities
     */
    private $updatedFields = [];

    /**
     * Get the value of a field
     *
     * @param string $field
     * @return mixed
     */
    final protected function get(string $field)
    {
        if (!array_key_exists($field, $this->values)) {
            return null;
        }

        return $this->values[$field];
    }

    /**
     * Set the value of a field
     *
     * @param string $field
     * @param mixed $value
     */
    final protected function set($field, $value): void
    {
        if ($this->id !== null) {
            // value is the same, don't bother updating
            if (array_key_exists($field, $this->values) && $this->values[$field] === $value) {
                return;
            }

            $this->updatedFields[$field] = $value;
        }

        if ($field === 'id') {
            throw new Exception('Not possible to change ID');
        }

        $this->values[$field] = $value;
    }

    /**
     * Get the ID of the entity
     *
     * Only available for entities also in the database
     *
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
     * @param int $id
     */
    final public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get all the values needed to insert the entity
     *
     * @return mixed[]
     */
    final public function getInsertValues(): array
    {
        $values = [];
        $fields = static::fields();

        foreach ($fields as $field => $options) {
            if (isset($options['virtual']) && $options['virtual'] === true) {
                continue;
            } elseif (array_key_exists($field, $this->values)) {
                $value = $this->values[$field];
            } elseif (isset($options['default'])) {
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
            $values['created_at'] = $this->toDatabaseFormatValue(
                'datetime',
                new \DateTimeImmutable()
            );

            $values['updated_at'] = $this->toDatabaseFormatValue(
                'datetime',
                new \DateTimeImmutable()
            );
        }

        return $values;
    }

    /**
     * Get all updated field/values for entity
     *
     * @return mixed[]
     */
    final public function getUpdateValues(): array
    {
        $values = [];
        $fields = static::fields();

        foreach ($this->updatedFields as $field => $value) {
            if (isset($fields[$field])) {
                $options = $fields[$field];

                if (isset($options['virtual']) && $options['virtual'] === true) {
                    continue;
                }
            }

            $values[$field] = $this->toDatabaseFormat($field, $value);
        }

        if (!empty($values) && static::timestamps()) {
            $values['updated_at'] = $this->toDatabaseFormatValue(
                'datetime',
                new \DateTimeImmutable()
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
                if (static::timestamps() &&
                    ($field === self::CREATED_AT_FIELD ||
                    $field === self::UPDATED_AT_FIELD)
                ) {
                    $this->values[$field] = $this->fromDatabaseFormatValue(
                        'datetime',
                        $value
                    );

                    continue;
                }

                if (!array_key_exists($field, $this->values)) {
                    $this->values[$field] = $this->fromDatabaseFormat($field, $value);
                }
            }
        }
    }

    /**
     * Fill all values
     *
     * @param array $record
     */
    final public function hydrate(array $record): void
    {
        foreach ($record as $field => $value) {
            // the ID has special treatment
            if ($field === 'id') {
                continue;
            }

            if (static::timestamps() &&
                ($field === self::CREATED_AT_FIELD ||
                $field === self::UPDATED_AT_FIELD)
            ) {
                $this->values[$field] = $this->fromDatabaseFormatValue(
                    'datetime',
                    $value
                );

                continue;
            }

            $this->values[$field] = $this->fromDatabaseFormat($field, $value);
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
        $fields = static::fields();

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
            case 'bool':
                return intval($value);
            case 'datetime':
                return $this
                    ->fromMutable($value)
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format(self::DATETIME_FORMAT);
            case 'json':
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
        $fields = static::fields();

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
            case 'int':
                return intval($value);
            case 'bool':
                return boolval($value);
            case 'datetime':
                return \DateTimeImmutable::createFromFormat(
                    self::DATETIME_FORMAT,
                    $value,
                    new \DateTimeZone('UTC')
                );
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Make mutable date immutable, if needed
     *
     * @param \DateTimeInterface $date
     * @return \DateTimeImmutable
     */
    private static function fromMutable(\DateTimeInterface $date): \DateTimeImmutable
    {
        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }

        return \DateTimeImmutable::createFromMutable($date);
    }
}
