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

use Access\Cascade;
use Access\Database;
use Access\Entity;
use Access\Schema\Table;

/**
 * @psalm-template T of Entity
 */
class Reference extends Integer
{
    /**
     * Entity that is referenced
     *
     * @psalm-var class-string<T>|Table
     */
    private string|Table $table;

    private ?Cascade $cascade = null;

    /**
     * Create a field with a name
     *
     * @param class-string<T>|Table $table
     */
    public function __construct(Table|string $table, ?Cascade $cascade = null)
    {
        if (is_string($table)) {
            Database::assertValidEntityClass($table);
        }

        $this->table = $table;
        $this->cascade = $cascade;
    }

    /**
     * Get the name of the table
     */
    public function getTableName(): string
    {
        return is_string($this->table) ? $this->table::tableName() : $this->table->getName();
    }

    public function getCascade(): ?Cascade
    {
        return $this->cascade;
    }
}
