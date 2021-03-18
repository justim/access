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

namespace Tests;

use Tests\AbstractBaseTestCase;

/**
 * SQLite has no support for locks
 *
 * Add some tests when we run our tests on a different database
 */
class LockTest extends AbstractBaseTestCase
{
    public function testLockRead(): void
    {
        $this->assertTrue(true);
    }

    public function testLockWrite(): void
    {
        $this->assertTrue(true);
    }

    public function testLocked(): void
    {
        $this->assertTrue(true);
    }
}
