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

namespace Access\Driver\Sqlite;

use Access\Clause\Field;
use Access\Driver\Driver;

/**
 * SQLite specific driver
 *
 * @author Tim <me@justim.net>
 * @internal
 */
class Sqlite extends Driver
{
    public const NAME = 'sqlite';

    /**
     * Escape identifier
     *
     * @param string|Field $identifier Identifier to escape
     * @return string
     * @internal
     */
    public function escapeIdentifier(string|Field $identifier): string
    {
        if ($identifier instanceof Field) {
            $identifier = $identifier->getName();
        }

        return str_replace('.', '"."', sprintf('"%s"', str_replace('"', '""', $identifier)));
    }

    /**
     * Get a debug string value for a value in SQLite dialect
     *
     * Useful for the debug query, should not be used otherwise, use prepared statements
     */
    public function getDebugStringValue(mixed $value): string
    {
        return sprintf("'%s'", addslashes((string) $value));
    }

    /**
     * Get the function name for random in SQLite dialect
     */
    public function getFunctionNameRandom(): string
    {
        return 'RANDOM()';
    }

    /**
     * Has the SQLite driver support for LOCK/UNLOCK TABLES?
     */
    public function hasLockSupport(): bool
    {
        return false;
    }
}
