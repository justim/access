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
     * @param Collection $collection Collection to filter
     */
    public function filterCollection(Collection $collection): Collection;

    /**
     * Create the finder function for this filter clause
     *
     * @return callable
     * @psalm-return callable(\Access\Entity): scalar
     */
    public function createFilterFinder(): callable;
}
