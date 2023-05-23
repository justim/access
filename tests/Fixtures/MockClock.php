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

namespace Tests\Fixtures;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Mock implementation of the ClockInterface, will use the time set in the constructor
 */
class MockClock implements ClockInterface
{
    private DateTimeImmutable $now;

    /**
     * Create a mock clock with a give datetime
     */
    public function __construct(DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new DateTimeImmutable();
    }

    /**
     * Return the mocked datetime from the constructor
     */
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    /**
     * Set the internal datetime of the clock
     */
    public function set(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}
