<?php

declare(strict_types=1);

namespace Access;

use Access\Collection;

/**
 * Batch of entities
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
