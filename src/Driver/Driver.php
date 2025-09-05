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

/**
 * Base driver
 *
 * @author Tim <me@justim.net>
 * @internal
 */
abstract class Driver implements DriverInterface
{
    /**
     * Get a debug string value for a value
     *
     * After processing by query
     *
     * Useful for the debug query, should not be used otherwise, use prepared statements
     *
     * @return string Save'ish converted SQL value
     */
    public function getDebugSqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        // Check if result is non-unicode string using PCRE_UTF8 modifier
        // see DoctrineBundle escape function
        if (is_string($value) && !preg_match('//u', $value)) {
            return '0x' . strtoupper(bin2hex($value));
        }

        // bools and dates are already processed

        return $this->getDebugStringValue($value);
    }
}
