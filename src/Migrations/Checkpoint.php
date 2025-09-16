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

namespace Access\Migrations;

/**
 * Migration checkpoint to track steps
 *
 * Used to skip migration steps that have already been executed; useful when
 * creating/developing migrations and a query fails. With a checkpoint,
 * successful steps of a migration can be skipped.
 *
 * @author Tim <me@justim.net>
 */
class Checkpoint
{
    public function __construct(private int $step = 0) {}

    public function getStep(): int
    {
        return $this->step;
    }

    /**
     * Should the migration step be skipped
     */
    public function shouldSkip(int $step): bool
    {
        return $step < $this->step;
    }

    /**
     * Advance the checkpoint to the next step
     */
    public function advance(): void
    {
        $this->step += 1;
    }
}
