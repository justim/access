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

use Access\Clause\ConditionInterface;
use Access\Entity;
use Access\Query\QueryGeneratorState;

/**
 * Multiple clauses to mixed and/or match (condition matches with "or")
 *
 * When you want to sort a collection _and_ filter some of the entities
 *
 * @author Tim <me@justim.net>
 */
class MultipleOr extends Multiple
{
    /**
     * Test given entity against conditions
     *
     * Must match _any_ sub-condition to match
     *
     * @param Entity|null $entity Entity to compare
     * @return bool Does the condition match the entity
     */
    public function matchesEntity(?Entity $entity): bool
    {
        foreach ($this->clauses as $clause) {
            if ($clause instanceof ConditionInterface) {
                if ($clause->matchesEntity($entity)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionSql(QueryGeneratorState $state): string
    {
        return $this->getMultipleSql(self::COMBINE_WITH_OR, $state);
    }
}
