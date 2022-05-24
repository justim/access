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

namespace Access\Clause\Filter;

use Access\Clause\FilterInterface;
use Access\Collection;

/**
 * Filter clause
 *
 * @author Tim <me@justim.net>
 */
abstract class Filter implements FilterInterface
{
    /**
     * Filter given collection in place based on this filter clause
     *
     * @param Collection $collection Collection to filter
     */
    public function filterCollection(Collection $collection): Collection
    {
        return $collection->filter($this->createFilterFinder());
    }
}
