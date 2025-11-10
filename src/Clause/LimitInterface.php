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

use Access\Collection;
use Access\Query\QueryGeneratorState;

/**
 * Clause is limiting
 *
 * @author Tim <me@justim.net>
 */
interface LimitInterface extends ClauseInterface
{
    /**
     * Get the limit of number of entities
     */
    public function getLimit(): int;

    /**
     * Get the starting offset
     */
    public function getOffset(): ?int;

    /**
     * Limit given collection in place based on this limit clause
     *
     * @param Collection|null $collection Collection to limit
     */
    public function limitCollection(?Collection $collection): void;

    /**
     * Create the SQL for limit clause
     *
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
