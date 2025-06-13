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
use Access\Query\QueryGeneratorState;

/**
 * Multiple clauses to mixed and/or match
 *
 * When you want to sort a collection _and_ filter some of the entities
 *
 * @author Tim <me@justim.net>
 */
class Multiple implements ConditionInterface, OrderByInterface, FilterInterface, \Countable
{
    /**
     * Combinator for AND
     * @var string
     */
    protected const COMBINE_WITH_AND = ' AND ';

    /**
     * Combinator for OR
     * @var string
     */
    protected const COMBINE_WITH_OR = ' OR ';

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
     * Return the number of clauses currently available
     */
    public function count(): int
    {
        return count($this->clauses);
    }

    /**
     * Add an extra clauses to this multiple
     *
     * @param ClauseInterface $clauses Extra clauses
     * @return static The current multiple
     */
    public function add(ClauseInterface ...$clauses): static
    {
        $this->clauses = array_merge($this->clauses, $clauses);

        return $this;
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

    /**
     * Filter given collection in place based on this filter clause
     *
     * @param Collection $collection Collection to filter
     */
    public function filterCollection(Collection $collection): Collection
    {
        return $collection->filter($this->createFilterFinder());
    }

    /**
     * Create the finder function for this filter clause
     *
     * @return callable
     * @psalm-return callable(\Access\Entity): scalar
     */
    public function createFilterFinder(): callable
    {
        /**
         * @var callable[] $finders
         * @psalm-var array<array-key, callable(Entity, Entity): int> $finders
         */
        $finders = [];

        foreach ($this->clauses as $clause) {
            if ($clause instanceof FilterInterface) {
                $finders[] = $clause->createFilterFinder();
            }
        }

        return function (Entity $entity) use ($finders): bool {
            foreach ($finders as $finder) {
                $found = $finder($entity);

                if ($found === false) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionSql(QueryGeneratorState $state): string
    {
        return $this->getMultipleSql(self::COMBINE_WITH_AND, $state);
    }

    /**
     * Get SQL for multiple conditions
     *
     * @param string $combineWith Combine the conditions with (ex. AND/OR)
     * @param QueryGeneratorState $state A bit of state for query generation
     */
    protected function getMultipleSql(string $combineWith, QueryGeneratorState $state): string
    {
        $conditionParts = [];

        // without any clauses, we can't determine if this is a multiple condition.
        // to be on the safe side, we assume it is a multiple condition
        $isMultipleCondition = count($this->clauses) === 0;

        foreach ($this->clauses as $clause) {
            if (
                $state->getContext() === QueryGeneratorStateContext::Condition &&
                $clause instanceof ConditionInterface
            ) {
                $isMultipleCondition = true;
                $conditionParts[] = $clause->getConditionSql($state);
            } elseif (
                $state->getContext() === QueryGeneratorStateContext::OrderBy &&
                $clause instanceof OrderByInterface
            ) {
                $conditionParts[] = $clause->getConditionSql($state);
            }
        }

        if (empty($conditionParts)) {
            if ($state->getContext()->allowEmptyMultiple()) {
                return '';
            }

            if ($isMultipleCondition) {
                // empty conditions make no sense...
                // droppping the whole condition is risky because you may
                // over-select a whole bunch of records, better is to under-select.
                return '1 = 2';
            }

            return '';
        }

        $combinedConditions = implode($combineWith, $conditionParts);

        if (count($conditionParts) > 1) {
            // make sure to enclode the conditions with parentheses to make
            // sure specificity stays in tact
            return sprintf('(%s)', $combinedConditions);
        }

        return $combinedConditions;
    }

    /**
     * {@inheritdoc}
     */
    public function injectConditionValues(QueryGeneratorState $state): void
    {
        foreach ($this->clauses as $clause) {
            if (
                $state->getContext() === QueryGeneratorStateContext::Condition &&
                $clause instanceof ConditionInterface
            ) {
                $clause->injectConditionValues($state);
            } elseif (
                $state->getContext() === QueryGeneratorStateContext::OrderBy &&
                $clause instanceof OrderByInterface
            ) {
                $clause->injectConditionValues($state);
            }
        }
    }
}
