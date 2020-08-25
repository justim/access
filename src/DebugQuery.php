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

namespace Access;

use Access\Query;

/**
 * Debug your query
 *
 * @author Tim <me@justim.net>
 */
class DebugQuery
{
    /**
     * @var Query
     */
    private Query $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Get a "runnable" version of the query
     *
     * NOTE: this should not be used in production
     *
     * @return string|null Runnable query
     */
    public function toRunnableQuery(): ?string
    {
        $sql = $this->query->getSql();

        if ($sql === null) {
            return null;
        }

        $values = $this->query->getValues();

        foreach ($values as $placeholder => $value) {
            $sql = preg_replace("/:$placeholder\b/", $this->toSqlValue($value), $sql);
        }

        return $sql;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function toSqlValue($value): string
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

        return sprintf('"%s"', addslashes((string) $value));
    }
}
