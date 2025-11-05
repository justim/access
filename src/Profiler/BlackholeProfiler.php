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

namespace Access\Profiler;

use Access\Profiler;
use Access\Query;

/**
 * A blackhole version of the profiler
 *
 * Does not keep track of any queries
 *
 * @author Tim <me@justim.net>
 */
class BlackholeProfiler extends Profiler
{
    /**
     * Create a query profile for query
     *
     * Does not keep it in history
     */
    public function createForQuery(Query $query): QueryProfile
    {
        return new QueryProfile($query);
    }
}
