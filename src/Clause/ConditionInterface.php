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

use Access\Entity;
use Access\Query\QueryGeneratorState;

/**
 * Clause is a condition
 *
 * @author Tim <me@justim.net>
 */
interface ConditionInterface extends ClauseInterface
{
    /**
     * Test given entity against condition
     *
     * @param Entity|null $entity Entity to compare
     * @return bool Does the condition match the entity
     */
    public function matchesEntity(?Entity $entity): bool;

    /**
     * Create the SQL for condition
     *
     * @param QueryGeneratorState $state A bit of state for query generation
     * @internal
     */
    public function getConditionSql(QueryGeneratorState $state): string;

    /**
     * Inject SQL values into indexed values
     *
     * @param QueryGeneratorState $state A bit of state for query generation
     * @internal
     */
    public function injectConditionValues(QueryGeneratorState $state): void;
}
