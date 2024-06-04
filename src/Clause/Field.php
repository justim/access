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

namespace Access\Clause;

/**
 * A simple field reference
 *
 * @author Tim <me@justim.net>
 */
class Field
{
    /**
     * Name of the field
     */
    private string $name;

    /**
     * Create a field with a name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of the field
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add the table name to the field name if it doesn't already have it
     */
    public function maybeAddTableName(string $tableName): void
    {
        if (str_contains($this->name, '.')) {
            return;
        }

        $this->name = $tableName . '.' . $this->name;
    }
}
