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

namespace Access\Query;

/**
 * Filter information about including soft deleted entities
 */
enum IncludeSoftDeletedFilter
{
    /**
     * Exclude soft deleted entities
     */
    case Exclude;

    /**
     * Include soft deleted entities
     */
    case Include;

    /**
     * No preference, use the value set by query
     */
    case Auto;
}
