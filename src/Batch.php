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

namespace Access;

use Access\Collection;

/**
 * Batch of entities
 *
 * Specialized implementation of a collection
 *
 * @author Tim <me@justim.net>
 */
class Batch extends Collection
{
    private const MAX_BATCH_SIZE = 100;

    /**
     * Is this batch full?
     *
     * @return bool
     */
    public function isFull(): bool
    {
        return count($this) === self::MAX_BATCH_SIZE;
    }
}
