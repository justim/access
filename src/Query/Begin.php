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

namespace Access\Query;

use Access\Driver\DriverInterface;
use Access\Query;

/**
 * Create BEGIN query
 *
 * @author Tim <me@justim.net>
 */
class Begin extends Query
{
    /**
     * Create a BEGIN query
     */
    public function __construct()
    {
        parent::__construct('__dummy__');
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(?DriverInterface $driver = null): ?string
    {
        return 'BEGIN';
    }
}
