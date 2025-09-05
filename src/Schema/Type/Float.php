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

use Access\Schema\Exception\InvalidDatabaseValueException;
use Access\Schema\Type;

class FloatType extends Type
{
    public function __construct() {}

    public function fromDatabaseFormatValue(mixed $value): float
    {
        if (!is_float($value)) {
            throw new InvalidDatabaseValueException('Invalid float type: ' . gettype($value));
        }

        return $value;
    }

    public function toDatabaseFormatValue(mixed $value): float
    {
        return (float) $value;
    }
}
