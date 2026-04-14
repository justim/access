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

use Access\Schema\Type;

/**
 * A mixed type to just pass raw values around
 *
 * Should not be used in a schema, but can be helpful for virtual field providers with a dynamic type.
 *
 * @internal
 */
class VirtualMixedType extends Type
{
    public function fromDatabaseFormatValue(mixed $value): mixed
    {
        return $value;
    }

    public function toDatabaseFormatValue(mixed $value): string
    {
        return (string) $value;
    }
}
