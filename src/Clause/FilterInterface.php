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

use Access\Clause\Filter\FilterItemResult;
use Access\Collection;

/**
 * Clause is for filtering
 *
 * @author Tim <me@justim.net>
 */
interface FilterInterface extends ClauseInterface
{
    /**
     * Filter given collection in place based on this filter clause
     *
     * @psalm-template TEntity of \Access\Entity
     * @param Collection $collection The collection to filter
     * @psalm-param Collection<TEntity> $collection The collection to filter
     * @return Collection The filtered collection
     * @psalm-return Collection<TEntity> The filtered collection
     */
    public function filterCollection(Collection $collection): Collection;

    /**
     * Create the finder function for this filter clause
     *
     * @return callable
     * @psalm-return callable(\Access\Entity): (FilterItemResult|bool)
     */
    public function createFilterFinder(): callable;
}
