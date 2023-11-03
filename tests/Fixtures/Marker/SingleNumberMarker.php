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

namespace Tests\Fixtures\Marker;

use Access\Presenter\CustomMarkerInterface;

class SingleNumberMarker implements CustomMarkerInterface
{
    public function __construct(private int $number)
    {
    }

    public function fetch(): mixed
    {
        return $this->number;
    }
}
