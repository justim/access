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

class Boolean extends Type
{
    public function __construct() {}

    public function fromDatabaseFormatValue(mixed $value): bool
    {
        if (!is_int($value)) {
            throw new InvalidDatabaseValueException('Invalid boolean type: ' . gettype($value));
        }

        return (bool) $value;
    }

    public function toDatabaseFormatValue(mixed $value): int
    {
        return intval($value);
    }
}
