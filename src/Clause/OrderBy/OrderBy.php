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

namespace Access\Clause\OrderBy;

use Access\Clause\Condition\Raw;
use Access\Clause\Field;
use Access\Clause\OrderByInterface;
use Access\Collection;
use Access\Entity;
use Access\Query;
use Access\Query\QueryGeneratorState;

/**
 * Sort clause
 *
 * @author Tim <me@justim.net>
 */
abstract class OrderBy implements OrderByInterface
{
    /**
     * @deprecated
     */
    protected const DESCENDING = 'DESC';

    /**
     * @deprecated
     */
    protected const ASCENDING = 'ASC';

    /**
     * Field to sort on
     */
    private Field|Raw $field;

    /**
     * Direction to sort on
     */
    private Direction $direction;

    /**
     * Create a sort clause for field and direction
     *
     * @param string|Field|Raw $fieldName Field to sort on
     * @param Direction|string $direction Direction to sort on
     */
    protected function __construct(string|Field|Raw $fieldName, Direction|string $direction)
    {
        if (is_string($fieldName)) {
            $fieldName = new Field($fieldName);
        }

        $this->field = $fieldName;

        if (is_string($direction)) {
            $direction = Direction::from($direction);
        }

        $this->direction = $direction;
    }

    public function getField(): Field|Raw
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function sortCollection(?Collection $collection): void
    {
        if ($collection === null) {
            return;
        }

        $collection->sort($this->createSortComparer());
    }

    /**
     * {@inheritdoc}
     */
    public function createSortComparer(): callable
    {
        return function (Entity $one, Entity $two): int {
            if ($this->field instanceof Raw) {
                // not possible to sort on raw field in PHP
                return 0;
            } elseif ($this->field->getName() === 'id') {
                $valueOne = $one->getId();
                $valueTwo = $two->getId();
            } else {
                $valuesOne = $one->getValues();
                $valuesTwo = $two->getValues();

                if (
                    !array_key_exists($this->field->getName(), $valuesOne) ||
                    !array_key_exists($this->field->getName(), $valuesTwo)
                ) {
                    return 0;
                }

                /** @var mixed $valueOne */
                $valueOne = $valuesOne[$this->field->getName()];
                /** @var mixed $valueTwo */
                $valueTwo = $valuesTwo[$this->field->getName()];
            }

            // special cases for string, natural sorting is a lot nicer
            if (is_string($valueOne) && is_string($valueTwo)) {
                if ($this->direction === Direction::Descending) {
                    return strnatcasecmp($valueTwo, $valueOne);
                } else {
                    return strnatcasecmp($valueOne, $valueTwo);
                }
            }

            if ($this->direction === Direction::Descending) {
                return $valueTwo <=> $valueOne;
            } else {
                return $valueOne <=> $valueTwo;
            }
        };
    }

    public function getConditionSql(QueryGeneratorState $state): string
    {
        $driver = $state->getDriver();

        if ($this->field instanceof Raw) {
            $condition = $this->field->getField()->getName();
        } else {
            $condition = $driver->escapeIdentifier($this->field->getName());
        }

        return sprintf('%s %s', $condition, $this->direction->toDatabaseFormat());
    }

    public function injectConditionValues(QueryGeneratorState $state): void
    {
        // no values to inject
    }
}
