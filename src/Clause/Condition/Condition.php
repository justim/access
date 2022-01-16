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
    private string $fieldName;

    /**
     * What kind of condition
     *
     * @psalm-var Condition::KIND_* $kind
     */
    private string $kind;

    /**
     * Value to compare to
     *
     * @var mixed
     */
    private mixed $value;

    /**
     * Create condition for given field name and value
     *
     * @param string $fieldName
     * @param string $kind
     * @psalm-param Condition::KIND_* $kind
     * @param mixed $value
     */
    protected function __construct(string $fieldName, string $kind, mixed $value)
    {
        $this->fieldName = $fieldName;
        $this->kind = $kind;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function matchesEntity(?Entity $entity): bool
    {
        if ($entity === null) {
            return false;
        }

        if ($this->fieldName === 'id') {
            $value = $entity->getId();
        } else {
            $values = $entity->getValues();

            if (!array_key_exists($this->fieldName, $values)) {
                return false;
            }

            /** @var mixed $value */
            $value = $values[$this->fieldName];
        }

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
            default => false
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
            return $haystack->hasEntityWith($this->fieldName, $needle);
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
        $escapedFieldName = Query::escapeIdentifier($this->fieldName);

        $condition = match ($this->kind) {
            self::KIND_EQUALS => sprintf('%s = ?', $escapedFieldName),
            self::KIND_NOT_EQUALS => sprintf('%s != ?', $escapedFieldName),
            self::KIND_GREATER_THAN => sprintf('%s > ?', $escapedFieldName),
            self::KIND_GREATER_THAN_OR_EQUALS => sprintf('%s >= ?', $escapedFieldName),
            self::KIND_LESS_THAN => sprintf('%s < ?', $escapedFieldName),
            self::KIND_LESS_THAN_OR_EQUALS => sprintf('%s <= ?', $escapedFieldName),
            self::KIND_IN => sprintf('%s IN (?)', $escapedFieldName),
            self::KIND_NOT_IN => sprintf('%s NOT IN (?)', $escapedFieldName),
            self::KIND_RAW => sprintf('(%s)', $this->fieldName),
            self::KIND_RELATION => sprintf(
                '%s = %s',
                $escapedFieldName,
                Query::escapeIdentifier($this->value),
            ),
            default => throw new Exception(sprintf('Invalid kind of condition: "%s"', $this->kind))
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
                (string) $this->value->getSql(),
            );

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
        }

        return $condition;
    }

    /**
     * {@inheritdoc}
     */
    public function injectConditionValues(QueryGeneratorState $state): void
    {
        if ($this->kind === self::KIND_RELATION) {
            // the value is used a field name
        } elseif ($this->value instanceof Select) {
            $state->addSubQueryValues($this->value);
        } elseif ($this->value === null) {
            // sql is converted to `IS NULL`
        } elseif (is_array($this->value) || $this->value instanceof Collection) {
            $values = Query::toDatabaseFormat($this->value);

            // empty list will result in no emitted values, this links up with
            // the `1 = 2` from the query itself when there are not values
            foreach ($values as $itemValue) {
                $state->addConditionValue($itemValue);
            }
        } else {
            $state->addConditionValue(Query::toDatabaseFormat($this->value));
        }
    }
}
