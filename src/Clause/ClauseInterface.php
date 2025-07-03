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

/**
 * Clause to manipulate entity/collection
 *
 * @author Tim <me@justim.net>
 */
interface ClauseInterface
{
    /**
     * Is another clause equal to this one
     *
     * @param ClauseInterface $clause Clause to compare with
     * @return bool Are the clauses equal
     */
    public function equals(ClauseInterface $clause): bool;
}
