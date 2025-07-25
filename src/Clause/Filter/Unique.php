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

namespace Access\Clause\Filter;

use Access\Clause\ClauseInterface;
use Access\Clause\Filter\Filter;
use Access\Entity;

/**
 * Unique filter clause
 *
 * @author Tim <me@justim.net>
 */
class Unique extends Filter
{
    private string $fieldName;

    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * Is this filter equal to another clause
     *
     * @param ClauseInterface $clause Clause to compare with
     * @return bool Are the clauses equal
     */
    public function equals(ClauseInterface $clause): bool
    {
        if ($this::class !== $clause::class) {
            return false;
        }

        /** @var static $clause */

        return $this->fieldName === $clause->fieldName;
    }

    /**
     * Create the finder function for this filter clause
     *
     * @return callable
     * @psalm-return callable(\Access\Entity): scalar
     */
    public function createFilterFinder(): callable
    {
        /** @var mixed[] $matchedValues */
        $matchedValues = [];

        return function (Entity $entity) use (&$matchedValues): bool {
            /** @var mixed[] $matchedValues */

            /** @var array<string, mixed> $values */
            $values = array_merge($entity->getValues(), [
                'id' => $entity->getId(),
            ]);

            if (!isset($values[$this->fieldName])) {
                return false;
            }

            /** @var mixed $value */
            $value = $values[$this->fieldName];

            if (in_array($value, $matchedValues, true)) {
                return false;
            }

            /**
             * Assigning a mixed value is very much on purpose, the `use` part seems to disrupt things
             * @psalm-suppress MixedAssignment
             * @var mixed[] $matchedValues
             */
            $matchedValues[] = $value;

            return true;
        };
    }
}
