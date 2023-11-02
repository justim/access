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

use Access\Query;

/**
 * Create COMMIT query
 *
 * @author Tim <me@justim.net>
 */
class Commit extends Query
{
    /**
     * Create a COMMIT query
     */
    public function __construct()
    {
        parent::__construct('__dummy__');
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(): ?string
    {
        return 'COMMIT';
    }
}
