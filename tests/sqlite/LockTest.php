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

namespace Tests\Sqlite;

use Tests\Base\BaseLockTest;

/**
 * SQLite has no support for locks, the tests are just "covering" the lock code
 *
 * Add some tests when we run our tests on a different database
 */
class LockTest extends BaseLockTest
{
    use DatabaseBuilderTrait;
}
