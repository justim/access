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

namespace Access\Schema;

abstract class Type
{
    /**
     * Convert a value from the database format to the PHP format
     */
    abstract function fromDatabaseFormatValue(mixed $value): mixed;

    /**
     * Convert a value from the PHP format to the database format
     */
    abstract function toDatabaseFormatValue(mixed $value): mixed;
}
