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

namespace Access\Clause\Condition;

use Access\Clause\ConditionInterface;
use Access\Clause\Field;
use Access\Collection;
use Access\Entity;
use Access\Exception;
use Access\Query;
use Access\Query\QueryGeneratorState;
use Access\Query\Select;

/**
 * Condition clause
 *
 * @author Tim <me@justim.net>
 */
abstract class Condition implements ConditionInterface
{
    protected const KIND_EQUALS = 'EQUALS';
    protected const KIND_NOT_EQUALS = 'NOT_EQUALS';
    protected const KIND_GREATER_THAN = 'GREATER_THAN';
    protected const KIND_GREATER_THAN_OR_EQUALS = 'GREATER_THAN_OR_EQUALS';
    protected const KIND_LESS_THAN = 'LESS_THAN';
    protected const KIND_LESS_THAN_OR_EQUALS = 'LESS_THAN_OR_EQUALS';
    protected const KIND_IN = 'IN';
    protected const KIND_NOT_IN = 'NOT_IN';

    /**
     * The field name is used directly in the SQL query
     * Use with care
     */
    protected const KIND_RAW = 'RAW';

    /**
     * Value is used as a field name
     */
    protected const KIND_RELATION = 'RELATION';

    /**
     * Name of the field to compare
     */
    private Field $field;

    /**
     * What kind of condition
     *
     * @psalm-var Condition::KIND_* $kind
     */
    private string $kind;

    /**
     * Value to compare to, or a `Field` to reference another field
     *
     * @var mixed
     */
    private mixed $value;

    /**
     * Create condition for given field name and value
     *
     * @param string|Field $fieldName
     * @param string $kind
     * @psalm-param Condition::KIND_* $kind
     * @param mixed $value Value to compare to, or a `Field` to reference another field
     */
    protected function __construct(string|Field $fieldName, string $kind, mixed $value)
    {
        if (is_string($fieldName)) {
            $fieldName = new Field($fieldName);
        }

        $this->field = $fieldName;
        $this->kind = $kind;
        $this->value = $value;
    }

    /**
     * Get the field to compare
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function matchesEntity(?Entity $entity): bool
    {
        if ($entity === null) {
            return false;
        }

        if ($this->value instanceof Field) {
            // TODO: support other field values
            return false;
        }

        if ($this->field->getName() === 'id') {
            $value = $entity->getId();
        } else {
            $values = $entity->getValues();

            if (!array_key_exists($this->field->getName(), $values)) {
                return false;
            }

            /** @var mixed $value */
            $value = $values[$this->field->getName()];
        }

        /**
         * SAFETY technically it is possible to have other values here,
         * regardless, having a `default` is a good idea here
         * @psalm-suppress DocblockTypeContradiction
         *
         * SAFETY the parent class guarantees the type of the value
         * @psalm-suppress MixedArgument
         */
        return match ($this->kind) {
            self::KIND_EQUALS => $value === $this->value,
            self::KIND_NOT_EQUALS => $value !== $this->value,
            self::KIND_GREATER_THAN => $value > $this->value,
            self::KIND_GREATER_THAN_OR_EQUALS => $value >= $this->value,
            self::KIND_LESS_THAN => $value < $this->value,
            self::KIND_LESS_THAN_OR_EQUALS => $value <= $this->value,
            self::KIND_IN => $this->contains($value, $this->value),
            self::KIND_NOT_IN => !$this->contains($value, $this->value),
            self::KIND_RAW, self::KIND_RELATION => false,
            /** @phpstan-ignore match.unreachable */
            default => false,
        };
    }

    /**
     * Does the haystack contain the needle?
     *
     * Does some special handling for collections
     *
     * @param mixed $needle Need to find
     * @param iterable $haystack Haystack to search
     * @return bool Is the need found?
     */
    private function contains(mixed $needle, iterable $haystack): bool
    {
        if ($haystack instanceof Collection) {
            return $haystack->hasEntityWith($this->field->getName(), $needle);
        }

        if (!is_array($haystack)) {
            $haystack = iterator_to_array($haystack, false);
        }

        return in_array($needle, $haystack, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionSql(QueryGeneratorState $state): string
    {
        $driver = $state->getDriver();

        $escapedFieldName = $driver->escapeIdentifier($this->field->getName());

        /**
         * SAFETY technically it is possible to have other values here,
         * regardless, having a `default` is a good idea here
         * @psalm-suppress DocblockTypeContradiction
         * @psalm-suppress NoValue
         *
         * SAFETY the parent class guarantees the type of the value
         * @psalm-suppress MixedArgument
         */
        $condition = match ($this->kind) {
            self::KIND_EQUALS => sprintf('%s = ?', $escapedFieldName),
            self::KIND_NOT_EQUALS => sprintf('%s != ?', $escapedFieldName),
            self::KIND_GREATER_THAN => sprintf('%s > ?', $escapedFieldName),
            self::KIND_GREATER_THAN_OR_EQUALS => sprintf('%s >= ?', $escapedFieldName),
            self::KIND_LESS_THAN => sprintf('%s < ?', $escapedFieldName),
            self::KIND_LESS_THAN_OR_EQUALS => sprintf('%s <= ?', $escapedFieldName),
            self::KIND_IN => sprintf('%s IN (?)', $escapedFieldName),
            self::KIND_NOT_IN => sprintf('%s NOT IN (?)', $escapedFieldName),
            self::KIND_RAW => sprintf('(%s)', $this->field->getName()),
            self::KIND_RELATION => sprintf(
                '%s = %s',
                $escapedFieldName,
                $driver->escapeIdentifier($this->value),
            ),
            /** @phpstan-ignore match.unreachable */
            default => throw new Exception(
                sprintf('Invalid kind of condition: "%s"', $this->kind),
            ),
        };

        if ($this->kind === self::KIND_RELATION) {
            // ignore value
            return $condition;
        }

        if ($this->value instanceof Select) {
            // prefix all placeholders to make them unique in _this_ query
            $subQuery = preg_replace(
                '/:(([a-z][0-9]+)+)/',
                ':' . $state->getSubQueryIndexPrefix() . '$1',
                (string) $this->value->getSql($state->getDriver()),
            );

            /**
             * The preg replacement does not fail without our regex, so we can safely
             * "cast" the variable
             */
            assert(is_string($subQuery));

            /** @var string $condition */
            $condition = preg_replace(
                ['/(!)?= ?\?/', '/(NOT)? IN ?\(\?\)/i'],
                [sprintf('$1= (%s)', $subQuery), sprintf('$1 IN (%s)', $subQuery)],
                $condition,
            );

            $state->incrementSubQueryIndex();
        } elseif ($this->value === null) {
            /** @var string $condition */
            $condition = preg_replace_callback_array(
                [
                    '/!= ?\?/' => fn() => 'IS NOT NULL',
                    '/= ?\?/' => fn() => 'IS NULL',
                ],
                $condition,
            );
        } elseif (is_array($this->value) || $this->value instanceof Collection) {
            if (count($this->value) > 0) {
                $condition = str_replace(
                    '?',
                    implode(', ', array_fill(0, count($this->value), '?')),
                    $condition,
                );
            } else {
                // empty collections make no sense...
                // droppping the whole condition is risky because you may
                // over-select a whole bunch of records, better is to
                // under-select.
                $condition = '1 = 2';
            }
        } elseif ($this->value instanceof Field) {
            $condition = str_replace('?', $driver->escapeIdentifier($this->value), $condition);
        }

        return $condition;
    }

    /**
     * {@inheritdoc}
     */
    public function injectConditionValues(QueryGeneratorState $state): void
    {
        if ($this->kind === self::KIND_RELATION || $this->value instanceof Field) {
            // the value is used a field name
        } elseif ($this->value instanceof Select) {
            $state->addSubQueryValues($this->value);
        } elseif ($this->value === null) {
            // sql is converted to `IS NULL`
        } elseif (is_array($this->value) || $this->value instanceof Collection) {
            $values = Query::toDatabaseFormat($this->value);

            // empty list will result in no emitted values, this links up with
            // the `1 = 2` from the query itself when there are not values
            /** @var mixed $itemValue */
            foreach ($values as $itemValue) {
                $state->addConditionValue($itemValue);
            }
        } else {
            $state->addConditionValue(Query::toDatabaseFormat($this->value));
        }
    }
}
