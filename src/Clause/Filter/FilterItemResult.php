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

/**
 * Controls the flow of filtering a list of items
 *
 * `FilterItemResult::Done` indicates that no further items should be considered
 *
 * @author Tim <me@justim.net>
 */
enum FilterItemResult
{
    /**
     * The item should be included
     */
    case Include;

    /**
     * The item should be excluded
     */
    case Exclude;

    /**
     * The filtering is done, no further items will be considered
     *
     * The current item will be excluded
     */
    case Done;
}
