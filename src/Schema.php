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

use Access\Schema\Table;

/**
 * The complete schema with all tables
 */
class Schema
{
    private array $tables = [];

    /**
     * Add a table to the schema
     */
    public function addTable(Table $table): void
    {
        $this->tables[] = $table;
    }

    /**
     * List all tables in the schema
     */
    public function getTables(): array
    {
        return $this->tables;
    }
}
