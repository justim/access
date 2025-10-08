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

namespace Access\Schema\Type;

class VarBinary extends StringType
{
    private int $size;

    public function __construct(int $size = 191)
    {
        $this->size = $size;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
