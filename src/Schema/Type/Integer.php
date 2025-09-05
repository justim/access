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

class Integer extends Type
{
    public function __construct() {}

    public function fromDatabaseFormatValue(mixed $value): int
    {
        // we are quite lenient here and accept strings and floats as well
        // this is to match our previous behavior in the entity class
        if (!is_int($value) && !is_string($value) && !is_float($value)) {
            throw new InvalidDatabaseValueException('Invalid integer type: ' . gettype($value));
        }

        // best effort :)
        return (int) $value;
    }

    public function toDatabaseFormatValue(mixed $value): int
    {
        return (int) $value;
    }
}
