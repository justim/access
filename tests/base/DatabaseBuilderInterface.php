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

namespace Tests\Base;

use Access\Database;
use Psr\Clock\ClockInterface;

interface DatabaseBuilderInterface
{
    public static function createDatabase(): Database;

    public static function createDatabaseWithDummyData(): Database;

    public static function createDatabaseWithMockClock(?ClockInterface $clock = null): Database;
}
