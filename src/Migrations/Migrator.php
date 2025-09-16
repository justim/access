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
use Access\Entity;
use Access\Exception\TableDoesNotExistException;
use Access\Migrations\Exception\MigrationFailedException;
use Access\Migrations\Exception\NotInitializedException;
use Access\Query\CreateTable;

/**
 * Migrator
 *
 * State machine:
 *
 * ```mermaid
 * stateDiagram-v2
 * [*] --> NotInitialized
 * NotInitialized --> Initialized : init()
 * Initialized --> ConstructiveExecuted : constructive()
 * ConstructiveExecuted --> ConstructiveReverted : revertConstructive()
 * ConstructiveExecuted --> DestructiveExecuted : destructive()
 * ConstructiveReverted --> ConstructiveExecuted : constructive()
 * DestructiveExecuted --> DestructiveReverted : revertDestructive()
 * DestructiveReverted --> DestructiveExecuted : destructive()
 * DestructiveReverted --> ConstructiveReverted : revertConstructive()
 * ```
 *
 * @author Tim <me@justim.net>
 */
class Migrator
{
    private Database $db;

    /**
     * @var class-string<Entity of MigrationEntity> $migrationsTableEntity
     */
    private string $migrationsTableEntity;

    private bool $dryRun = false;

    /**
     * @template TEntity of MigrationEntity
     * @param class-string<TEntity> $migrationsTableEntity
     */
    public function __construct(
        Database $db,
        string $migrationsTableEntity = MigrationEntity::class,
    ) {
        $this->db = $db;
        $this->migrationsTableEntity = $migrationsTableEntity;
    }

    public function init(): void
    {
        try {
            $migrationRecords = $this->db->getRepository($this->migrationsTableEntity)->findAll(1);
            iterator_to_array($migrationRecords);
        } catch (TableDoesNotExistException) {
            try {
                $table = $this->migrationsTableEntity::getTableSchema();

                $query = new CreateTable($table);

                $this->db->query($query);
            } catch (\Throwable $e) {
                throw new NotInitializedException(
                    'Could not create migrations table: ' . $e->getMessage(),
                    0,
                    $e,
                );
            }
        }
    }

    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    public function constructive(
        Migration $migration,
        Checkpoint $checkpoint = new Checkpoint(),
    ): MigrationResult {
        $checkpoint = clone $checkpoint;

        $migrationRecord = $this->getMigrationRecord($migration);

        if ($migrationRecord !== null && $migrationRecord->getConstructiveExecutedAt()) {
            return MigrationResult::alreadyExecuted();
        }

        $schemaChanges = new SchemaChanges();
        $migration->constructive($schemaChanges);

        if ($this->dryRun) {
            return MigrationResult::success($schemaChanges);
        }

        try {
            $schemaChanges->applyChanges($this->db, $checkpoint);
        } catch (\Throwable $e) {
            $result = MigrationResult::failure($schemaChanges, $checkpoint);
            throw new MigrationFailedException($result, $e);
        }

        if ($migrationRecord === null) {
            $migrationRecord = $this->createMigrationRecord($migration);
        }

        $migrationRecord->setConstructiveExecutedAt($this->db->now());
        $migrationRecord->setConstructiveRevertedAt(null);
        $this->db->save($migrationRecord);

        return MigrationResult::success($schemaChanges);
    }

    public function destructive(
        Migration $migration,
        Checkpoint $checkpoint = new Checkpoint(),
    ): MigrationResult {
        $checkpoint = clone $checkpoint;

        $migrationRecord = $this->getMigrationRecord($migration);

        if ($migrationRecord === null || $migrationRecord->getConstructiveExecutedAt() === null) {
            return MigrationResult::constructiveNotExecuted();
        }

        if ($migrationRecord->getDestructiveExecutedAt()) {
            return MigrationResult::alreadyExecuted();
        }

        $schemaChanges = new SchemaChanges();
        $migration->destructive($schemaChanges);

        if ($this->dryRun) {
            return MigrationResult::success($schemaChanges);
        }

        try {
            $schemaChanges->applyChanges($this->db, $checkpoint);
        } catch (\Throwable $e) {
            $result = MigrationResult::failure($schemaChanges, $checkpoint);
            throw new MigrationFailedException($result, $e);
        }

        $migrationRecord->setDestructiveExecutedAt($this->db->now());
        $migrationRecord->setDestructiveRevertedAt(null);
        $this->db->save($migrationRecord);

        return MigrationResult::success($schemaChanges);
    }

    public function revertConstructive(
        Migration $migration,
        Checkpoint $checkpoint = new Checkpoint(),
    ): MigrationResult {
        $checkpoint = clone $checkpoint;

        $migrationRecord = $this->getMigrationRecord($migration);

        if ($migrationRecord === null || $migrationRecord->getConstructiveExecutedAt() === null) {
            return MigrationResult::constructiveNotExecuted();
        }

        if (
            $migrationRecord->getDestructiveExecutedAt() !== null &&
            $migrationRecord->getDestructiveRevertedAt() === null
        ) {
            return MigrationResult::blockedByDestructiveChange();
        }

        if ($migrationRecord->getConstructiveRevertedAt() !== null) {
            return MigrationResult::alreadyExecuted();
        }

        $schemaChanges = new SchemaChanges();
        $migration->revertConstructive($schemaChanges);

        if ($this->dryRun) {
            return MigrationResult::success($schemaChanges);
        }

        try {
            $schemaChanges->applyChanges($this->db, $checkpoint);
        } catch (\Throwable $e) {
            $result = MigrationResult::failure($schemaChanges, $checkpoint);
            throw new MigrationFailedException($result, $e);
        }

        $migrationRecord->setConstructiveExecutedAt(null);
        $migrationRecord->setConstructiveRevertedAt($this->db->now());
        $this->db->save($migrationRecord);

        return MigrationResult::success($schemaChanges);
    }

    public function revertDestructive(
        Migration $migration,
        Checkpoint $checkpoint = new Checkpoint(),
    ): MigrationResult {
        $checkpoint = clone $checkpoint;

        $migrationRecord = $this->getMigrationRecord($migration);

        if ($migrationRecord === null || $migrationRecord->getConstructiveExecutedAt() === null) {
            return MigrationResult::constructiveNotExecuted();
        }

        if ($migrationRecord->getDestructiveExecutedAt() === null) {
            return MigrationResult::destructiveNotExecuted();
        }

        $schemaChanges = new SchemaChanges();
        $migration->revertDestructive($schemaChanges);

        if ($this->dryRun) {
            return MigrationResult::success($schemaChanges);
        }

        try {
            $schemaChanges->applyChanges($this->db, $checkpoint);
        } catch (\Throwable $e) {
            $result = MigrationResult::failure($schemaChanges, $checkpoint);
            throw new MigrationFailedException($result, $e);
        }

        $migrationRecord->setDestructiveExecutedAt(null);
        $migrationRecord->setDestructiveRevertedAt($this->db->now());
        $this->db->save($migrationRecord);

        return MigrationResult::success($schemaChanges);
    }

    private function getMigrationRecord(Migration $migration): ?MigrationEntity
    {
        /** @var MigrationEntity|null $migrationRecord */
        $migrationRecord = $this->db->getRepository($this->migrationsTableEntity)->findOneBy([
            'version' => $migration::class,
        ]);

        return $migrationRecord;
    }

    private function createMigrationRecord(Migration $migration): MigrationEntity
    {
        /**
         * The user wouldn't do this, right? Right?!
         * @psalm-suppress UnsafeInstantiation
         * @var MigrationEntity $migrationRecord
         */
        $migrationRecord = new $this->migrationsTableEntity();
        $migrationRecord->setVersion($migration::class);

        return $migrationRecord;
    }
}
