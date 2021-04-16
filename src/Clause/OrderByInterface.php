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
 * Clause is for sorting
 *
 * @author Tim <me@justim.net>
 */
interface OrderByInterface extends ClauseInterface
{
    /**
     * Sort given collection in place based on this sort clause
     *
     * @param Collection|null $collection Collection to sort
     */
    public function sortCollection(?Collection $collection): void;

    /**
     * Create the compare function for this sort clause
     *
     * @return callable
     * @psalm-return callable(\Access\Entity, \Access\Entity): int
     */
    public function createSortComparer(): callable;
}
