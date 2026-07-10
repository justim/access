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

use Access\Schema\Exception\InvalidValueException;
use Access\Schema\Type;

abstract class StringType extends Type
{
    public function fromDatabaseFormatValue(mixed $value): string
    {
        // cleanly castable to string without information loss,
        // floats are weird..
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidValueException('Invalid string type: ' . gettype($value));
        }

        return (string) $value;
    }

    public function toDatabaseFormatValue(mixed $value): string
    {
        return (string) $value;
    }
}
