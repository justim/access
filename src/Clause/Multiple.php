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

namespace Access\Clause;

use Access\Clause\ClauseInterface;
use Access\Clause\ConditionInterface;
use Access\Collection;
use Access\Entity;

/**
 * Multiple clauses to mixed and/or match
 *
 * When you want to sort a collection _and_ filter some of the entities
 *
 * @author Tim <me@justim.net>
 */
class Multiple implements ConditionInterface, OrderByInterface
{
    /**
     * @var ClauseInterface[]
     */
    protected array $clauses;

    /**
     * Create a clause with multiple sub-clauses
     */
    public function __construct(ClauseInterface ...$clauses)
    {
        $this->clauses = $clauses;
    }

    /**
     * Test given entity against conditions
     *
     * Must match _all_ sub-conditions to match
     *
     * @param Entity|null $entity Entity to compare
     * @return bool Does the condition match the entity
     */
    public function matchesEntity(?Entity $entity): bool
    {
        foreach ($this->clauses as $clause) {
            if ($clause instanceof ConditionInterface) {
                if (!$clause->matchesEntity($entity)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Sort given collection in place based on wrapped sort clauses
     *
     * Will use all sort clauses with a decreasing specificity
     *
     * @param Collection|null $collection Collection to sort
     */
    public function sortCollection(?Collection $collection): void
    {
        if ($collection === null) {
            return;
        }

        $collection->sort($this->createSortComparer());
    }

    /**
     * Create the compare function for wrapped sort clauses
     *
     * Will use all sort clauses with a decreasing specificity
     *
     * @return callable
     * @psalm-return callable(Entity, Entity): int
     */
    public function createSortComparer(): callable
    {
        /**
         * @var callable[] $comparers
         * @psalm-var array<array-key, callable(Entity, Entity): int> $comparers
         */
        $comparers = [];

        foreach ($this->clauses as $clause) {
            if ($clause instanceof OrderByInterface) {
                $comparers[] = $clause->createSortComparer();
            }
        }

        return function (Entity $one, Entity $two) use ($comparers): int {
            foreach ($comparers as $comparer) {
                $compareResult = $comparer($one, $two);

                if ($compareResult !== 0) {
                    return $compareResult;
                }
            }

            return 0;
        };
    }
}
