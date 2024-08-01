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

namespace Access\Driver;

/**
 * MySQL specific driver
 *
 * @author Tim <me@justim.net>
 * @internal
 */
class Mysql implements DriverInterface
{
    public const NAME = 'mysql';

    /**
     * Get the function name for random in MySQL dialect
     */
    public function getFunctionNameRandom(): string
    {
        return 'RAND()';
    }
}
