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

use Access\Schema\Charset;
use Access\Schema\Collate;
use Access\Schema\Table;

/**
 * The complete schema with all tables
 */
class Schema
{
    private string $name;

    private Charset $defautCharset = Charset::Utf8;
    private Collate $defaultCollate = Collate::Default;

    private array $tables = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultCharset(): Charset
    {
        return $this->defautCharset;
    }

    public function setDefaultCharset(Charset $charset): void
    {
        $this->defautCharset = $charset;
    }

    public function getDefaultCollate(): Collate
    {
        return $this->defaultCollate;
    }

    public function setDefaultCollate(Collate $collate): void
    {
        $this->defaultCollate = $collate;
    }

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
