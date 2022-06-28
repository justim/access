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

namespace Access\Clause\OrderBy;

/**
 * Direction for the order by clause
 *
 * @author Tim <me@justim.net>
 */
enum Direction: string
{
    /**
     * Order by ascending
     */
    case Ascending = 'ASC';

    /**
     * Order by descending
     */
    case Descending = 'DESC';
}
