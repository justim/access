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

class StatusFormatter
{
    public function format(string $progress): string
    {
        return ucfirst(strtolower(str_replace('_', ' ', $progress)));
    }
}
