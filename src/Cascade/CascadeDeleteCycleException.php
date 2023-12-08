<?php

/*
 * This file is part of the Access package.
 *
 * (c) Tim <me@justim.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Access\Cascade;

use Exception;

/**
 * Cascade delete cycle exception
 *
 * A cycle was detected in the cascade delete
 *
 * @author Tim <me@justim.net>
 */
class CascadeDeleteCycleException extends Exception
{
}
