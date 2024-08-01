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
 * Driver specific interface
 *
 * @author Tim <me@justim.net>
 * @internal
 */
interface DriverInterface
{
    /**
     * Get the function name for random in SQL dialect
     */
    public function getFunctionNameRandom(): string;
}
