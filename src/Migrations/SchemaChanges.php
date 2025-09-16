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

namespace Access\Migrations;

use Access\Database;
use Access\Query;
use Access\Query\AlterTable;
use Access\Query\CreateTable;
use Access\Query\DropTable;
use Access\Schema\Table;

enum ChangeType
{
    case CreateTable;
    case AlterTable;
    case DropTable;
    case Query;
}

/**
 * Schema change
 *
 * @author Tim <me@justim.net>
 */
class SchemaChanges
{
    /**
     * @var array<array{
     *  type: ChangeType,
     *  createTable?: Table,
     *  alterTable?: AlterTable,
     *  dropTable?: Table,
     *  query?: Query,
     * }>
     */
    private array $changes = [];

    public function createTable(
        string $name,
        bool $hasCreatedAt = false,
        bool $hasUpdatedAt = false,
        bool $hasDeletedAt = false,
    ): Table {
        $table = new Table($name, $hasCreatedAt, $hasUpdatedAt, $hasDeletedAt);

        $this->changes[] = [
            'type' => ChangeType::CreateTable,
            'createTable' => $table,
        ];

        return $table;
    }

    public function alterTable(string $name): AlterTable
    {
        $alterTable = new AlterTable(new Table($name));

        $this->changes[] = [
            'type' => ChangeType::AlterTable,
            'alterTable' => $alterTable,
        ];

        return $alterTable;
    }

    public function dropTable(string $name): void
    {
        $table = new Table($name);

        $this->changes[] = [
            'type' => ChangeType::DropTable,
            'dropTable' => $table,
        ];
    }

    public function query(Query $query): void
    {
        $this->changes[] = [
            'type' => ChangeType::Query,
            'query' => $query,
        ];
    }

    /**
     * @return Query[]
     */
    private function generateQueries(Checkpoint $checkpoint): array
    {
        $queries = [];
        $step = 0;

        foreach ($this->changes as $change) {
            if ($checkpoint->shouldSkip($step)) {
                $step++;
                continue;
            }

            switch ($change['type']) {
                case ChangeType::CreateTable:
                    assert(isset($change['createTable']), 'Create table must exist');

                    $query = new CreateTable($change['createTable']);
                    $queries[] = $query;
                    break;

                case ChangeType::AlterTable:
                    assert(isset($change['alterTable']), 'Alter table must exist');
                    $query = $change['alterTable'];
                    $queries[] = $query;
                    break;

                case ChangeType::DropTable:
                    assert(isset($change['dropTable']), 'Drop table must exist');
                    $query = new DropTable($change['dropTable']);
                    $queries[] = $query;
                    break;

                case ChangeType::Query:
                    assert(isset($change['query']), 'Query must exist');
                    $queries[] = $change['query'];
                    break;
            }

            $step++;
            $checkpoint->advance();
        }

        return $queries;
    }

    /**
     * Get queries starting from checkpoint
     *
     * @param Checkpoint $checkpoint Checkpoint to start from, will not be modified
     * @return Query[]
     */
    public function getQueries(Checkpoint $checkpoint = new Checkpoint()): array
    {
        $checkpoint = clone $checkpoint;

        return $this->generateQueries($checkpoint);
    }

    /**
     * Apply changes to the database
     *
     * @param Database $db Database instance
     * @param Checkpoint $checkpoint Checkpoint to start from, will be modified for each query
     */
    public function applyChanges(Database $db, Checkpoint $checkpoint = new Checkpoint()): void
    {
        foreach ($this->getQueries($checkpoint) as $query) {
            $db->query($query);

            $checkpoint->advance();
        }
    }
}
