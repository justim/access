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

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Simple clock to just return the current DateTimeImmutable
 */
class InternalClock implements ClockInterface
{
    /**
     * Returns the current time as a DateTimeImmutable Object
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
