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

use Access\Clause\OrderByInterface;
use Access\Collection;
use Access\Entity;

/**
 * Sort clause
 *
 * @author Tim <me@justim.net>
 */
abstract class OrderBy implements OrderByInterface
{
    protected const DESCENDING = 'DESC';
    protected const ASCENDING = 'ASC';

    /**
     * Field to sort on
     */
    private string $fieldName;

    /**
     * Direction to sort on
     */
    private string $direction;

    /**
     * Create a sort clause for field and direction
     *
     * @param string $fieldName Field to sort on
     * @param string $direction Direction to sort on
     */
    protected function __construct(string $fieldName, string $direction)
    {
        $this->fieldName = $fieldName;
        $this->direction = $direction;
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
            if ($this->fieldName === 'id') {
                $valueOne = $one->getId();
                $valueTwo = $two->getId();
            } else {
                $valuesOne = $one->getValues();
                $valuesTwo = $two->getValues();

                if (
                    !array_key_exists($this->fieldName, $valuesOne) ||
                    !array_key_exists($this->fieldName, $valuesTwo)
                ) {
                    return 0;
                }

                /** @var mixed $valueOne */
                $valueOne = $valuesOne[$this->fieldName];
                /** @var mixed $valueTwo */
                $valueTwo = $valuesTwo[$this->fieldName];
            }

            // special cases for string, natural sorting is a lot nicer
            if (is_string($valueOne) && is_string($valueTwo)) {
                if ($this->direction === self::DESCENDING) {
                    return strnatcasecmp($valueTwo, $valueOne);
                } else {
                    return strnatcasecmp($valueOne, $valueTwo);
                }
            }

            if ($this->direction === self::DESCENDING) {
                return $valueTwo <=> $valueOne;
            } else {
                return $valueOne <=> $valueTwo;
            }
        };
    }
}
