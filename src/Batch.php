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
    private const DEFAULT_BATCH_SIZE = 100;

    /**
     * Is this batch full?
     *
     * @param int|null $batchSize Size of the batches
     * @return bool
     */
    public function isFull(?int $batchSize = null): bool
    {
        $batchSize ??= self::DEFAULT_BATCH_SIZE;
        return count($this) >= $batchSize;
    }
}
