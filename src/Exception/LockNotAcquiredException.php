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

namespace Access\Exception;

use Access\Exception;

/**
 * Access specific "Lock not acquired" exception
 *
 * @author Tim <me@justim.net>
 */
class LockNotAcquiredException extends Exception {}
