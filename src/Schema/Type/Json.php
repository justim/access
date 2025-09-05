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
use Access\Schema\Exception\InvalidValueException;
use Access\Schema\Type;

class Json extends Type
{
    public function __construct() {}

    public function fromDatabaseFormatValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            throw new InvalidDatabaseValueException('Invalid JSON type: ' . gettype($value));
        }

        try {
            /** @var mixed $result */
            $result = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return $result;
        } catch (\JsonException $e) {
            throw new InvalidDatabaseValueException(
                'Invalid JSON value: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    public function toDatabaseFormatValue(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidValueException(
                'Failed to encode value as JSON: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
