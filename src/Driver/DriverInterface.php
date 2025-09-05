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

namespace Access\Driver;

use Access\Clause;

/**
 * Driver specific interface
 *
 * @author Tim <me@justim.net>
 * @internal
 */
interface DriverInterface
{
    /**
     * Escape identifier
     *
     * @param string|Clause\Field $identifier Identifier to escape
     * @return string
     * @internal
     */
    public function escapeIdentifier(string|Clause\Field $identifier): string;

    /**
     * Get a debug string value for a value
     *
     * After processing by query
     *
     * Useful for the debug query, should not be used otherwise, use prepared statements
     *
     * @return string Save'ish converted SQL value
     */
    public function getDebugSqlValue(mixed $value): string;

    /**
     * Get a debug string value for a value
     *
     * After processing by query
     *
     * Useful for the debug query, should not be used otherwise, use prepared statements
     *
     * @return string Save'ish converted SQL value
     */
    public function getDebugStringValue(mixed $value): string;

    /**
     * Get the function name for random in SQL dialect
     */
    public function getFunctionNameRandom(): string;

    /**
     * Has the driver support for LOCK/UNLOCK TABLES?
     */
    public function hasLockSupport(): bool;
}
