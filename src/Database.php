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

use Access\Entity;
use Access\Exception;
use Access\Lock;
use Access\Presenter;
use Access\Presenter\EntityPresenter;
use Access\Profiler;
use Access\Query;
use Access\Repository;
use Access\Statement;
use Access\StatementPool;
use Access\Transaction;

/**
 * An Access database
 *
 * Main entry for your database needs
 *
 * @author Tim <me@justim.net>
 */
class Database
{
    /**
     * PDO connection
     *
     * @var \PDO $connection
     */
    private \PDO $connection;

    /**
     * Statement pool
     *
     * @var StatementPool $statementPool
     */
    private StatementPool $statementPool;

    /**
     * Profiler
     *
     * @var Profiler $profiler
     */
    private Profiler $profiler;

    /**
     * Create a Access database with a PDO connection
     *
     * @param \PDO $connection A PDO connection
     * @param Profiler $profiler A custom profiler, optionally
     */
    public function __construct(\PDO $connection, Profiler $profiler = null)
    {
        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->connection = $connection;
        $this->statementPool = new StatementPool($this);
        $this->profiler = $profiler ?? new Profiler();
    }

    /**
     * Create a access database with a PDO connection string
     *
     * @param string $connectionString A PDO connection string
     * @param Profiler $profiler A custom profiler, optionally
     * @return self A Access database object
     */
    public static function create(string $connectionString, Profiler $profiler = null): self
    {
        try {
            $pdoConnection = new \PDO($connectionString);
            return new self($pdoConnection, $profiler);
        } catch (\Exception $e) {
            throw new Exception("Invalid database: {$connectionString}", 0, $e);
        }
    }

    /**
     * Get the PDO connection
     *
     * @return \PDO A PDO connection
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    /**
     * Get the statement pool
     *
     * @return StatementPool
     */
    public function getStatementPool(): StatementPool
    {
        return $this->statementPool;
    }

    /**
     * Get the profiler for some timings
     *
     * @return Profiler
     */
    public function getProfiler(): Profiler
    {
        return $this->profiler;
    }

    /**
     * Begin a transaction
     *
     * @return Transaction
     */
    public function beginTransaction(): Transaction
    {
        $transaction = new Transaction($this);
        $transaction->begin();

        return $transaction;
    }

    /**
     * Create a lock object
     *
     * @return Lock
     */
    public function createLock(): Lock
    {
        return new Lock($this);
    }

    /**
     * Get the repository to find entities
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-return Repository<TEntity>
     *
     * @param string $klass Entity class name
     */
    public function getRepository(string $klass): Repository
    {
        $this->assertValidEntityClass($klass);

        $repositoryClassName = $klass::getRepository();

        $this->assertValidRepositoryClass($repositoryClassName);

        /** @var Repository<TEntity> $repository
         * @psalm-suppress UnsafeInstantiation */
        $repository = new $repositoryClassName($this, $klass);

        return $repository;
    }

    /**
     * Find a single entity by its ID
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-return ?TEntity
     *
     * @param string $klass Entity class name
     * @param int $id ID of the entity
     * @return ?Entity
     */
    public function findOne(string $klass, int $id): ?Entity
    {
        return $this->getRepository($klass)->findOne($id);
    }

    /**
     * Find a single entity by searching for column values
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-return ?TEntity
     *
     * @param string $klass Entity class name
     * @param array<string, mixed> $fields List of fields with values
     * @return ?Entity
     */
    public function findOneBy(string $klass, array $fields): ?Entity
    {
        return $this->getRepository($klass)->findOneBy($fields);
    }

    /**
     * Find a list of entities by searching for column values
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-suppress InvalidReturnType TODO remove when psalm supports this
     * @psalm-return \Generator<int, TEntity, mixed, void> - yields Entity
     *
     * @param string $klass Entity class name
     * @param array<string, mixed> $fields List of fields with values
     * @param ?int $limit A a limit to the query
     * @return \Generator - yields Entity
     */
    public function findBy(string $klass, $fields, int $limit = null): \Generator
    {
        yield from $this->getRepository($klass)->findBy($fields, $limit);
    }

    /**
     * Find a list of entities by their ids
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-suppress InvalidReturnType TODO remove when psalm supports this
     * @psalm-return \Generator<int, TEntity, mixed, void> - yields Entity
     *
     * @param string $klass Entity class name
     * @param int[] $ids List of ids
     * @param ?int $limit A a limit to the query
     * @return \Generator - yields Entity
     */
    public function findByIds(string $klass, array $ids, int $limit = null): \Generator
    {
        yield from $this->getRepository($klass)->findByIds($ids, $limit);
    }

    /**
     * Find all entities (default sort `id ASC`)
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-suppress InvalidReturnType TODO remove when psalm supports this
     * @psalm-return \Generator<int, TEntity, mixed, void> - yields Entity
     *
     * @param string $klass Entity class name
     * @param ?int $limit A a limit to the query
     * @param string $orderBy The order to use to find all entities
     * @return \Generator - yields Entity
     */
    public function findAll(
        string $klass,
        ?int $limit = null,
        string $orderBy = 'id ASC',
    ): \Generator {
        yield from $this->getRepository($klass)->findAll($limit, $orderBy);
    }

    /**
     * Execute a select query with a entity provider
     *
     * @psalm-template TEntity of Entity
     * @psalm-return \Generator<int|null, TEntity, mixed, void> - yields Entity
     *
     * @param EntityProvider<TEntity> $entityProvider Creator the empty entity shells
     * @param Query\Select $query Select query to be executed
     * @return \Generator - yields Entity
     */
    public function selectWithEntityProvider(
        EntityProvider $entityProvider,
        Query\Select $query,
    ): \Generator {
        $stmt = new Statement($this, $this->profiler, $query);

        /** @var array<string, mixed> $record */
        foreach ($stmt->execute() as $record) {
            $model = $entityProvider->create();
            $model->hydrate($record);

            // not every model has an ID
            if ($model->hasId()) {
                yield $model->getId() => $model;
            } else {
                yield null => $model;
            }
        }
    }

    /**
     * Execute a select query
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-return \Generator<int|null, TEntity, mixed, void> - yields Entity
     *
     * @param string $klass Entity class name
     * @param Query\Select $query Select query to be executed
     * @return \Generator - yields Entity
     */
    public function select(string $klass, Query\Select $query): \Generator
    {
        $this->assertValidEntityClass($klass);

        $entityProvider = new EntityProvider($klass);

        return $this->selectWithEntityProvider($entityProvider, $query);
    }

    /**
     * Execute a select query and return the first entity
     *
     * @param string $klass Entity class name
     * @param Query\Select $query Select query to be executed
     * @return ?Entity
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-return ?TEntity
     */
    public function selectOne(string $klass, Query\Select $query): ?Entity
    {
        $query->limit(1);

        $records = iterator_to_array($this->select($klass, $query), false);

        if (empty($records)) {
            return null;
        }

        return array_shift($records);
    }

    /**
     * Insert a model
     *
     * The ID is set to the returned model
     *
     * @param Entity $model
     * @return Entity
     */
    public function insert(Entity $model): Entity
    {
        $this->assertValidEntityClass(get_class($model));

        $values = $model->getInsertValues();

        $query = new Query\Insert($model::tableName());
        $query->values($values);

        $stmt = new Statement($this, $this->profiler, $query);
        $gen = $stmt->execute();
        $model->setId(intval($gen->getReturn()));

        // set default values/timestamps
        $model->markUpdated($values);

        return $model;
    }

    /**
     * Send changes in model to database
     *
     * @param Entity $model
     * @return bool Was something actually updated
     */
    public function update(Entity $model): bool
    {
        $this->assertValidEntityClass(get_class($model));

        $id = $model->getId();
        $values = $model->getUpdateValues();

        $query = new Query\Update($model::tableName());
        $query->values($values);
        $query->where([
            'id = ?' => $id,
        ]);

        $stmt = new Statement($this, $this->profiler, $query);
        $gen = $stmt->execute();

        // set default values/timestamps
        $model->markUpdated($values);

        return $gen->getReturn() > 0;
    }

    /**
     * Save a model to the database
     *
     * Delegates to insert when no id is available, update otherwise
     *
     * @param Entity $model
     */
    public function save(Entity $model): void
    {
        if ($model->hasId()) {
            $this->update($model);
        } else {
            $this->insert($model);
        }
    }

    /**
     * Delete a model from the database
     *
     * @param Entity $model Model to delete
     * @return bool Was something actually deleted
     */
    public function delete(Entity $model): bool
    {
        $this->assertValidEntityClass(get_class($model));

        $id = $model->getId();

        $query = new Query\Delete($model::tableName());
        $query->where([
            'id = ?' => $id,
        ]);

        $stmt = new Statement($this, $this->profiler, $query);
        $gen = $stmt->execute();
        $model->markUpdated();

        return $gen->getReturn() > 0;
    }

    /**
     * Mark the entity as soft-deleted and save to database
     *
     * @param Entity $model Entity to soft delete
     * @return bool Was something actually soft deleted
     */
    public function softDelete(Entity $model): bool
    {
        if (!$model::isSoftDeletable()) {
            throw new Exception('Entity is not soft deletable');
        }

        try {
            $setDeletedAt = new \ReflectionMethod($model, 'setDeletedAt');

            if (!$setDeletedAt->isPublic()) {
                throw new Exception('Soft delete method is not public');
            }

            $setDeletedAt->invoke($model);

            return $this->update($model);
        } catch (\Exception $e) {
            throw new Exception('Entity is not soft deletable', 0, $e);
        }
    }

    /**
     * Execute a raw query
     *
     * Has no return value, not suited for select queries
     *
     * @param Query $query
     * @throws Exception when $query is a Query\Select
     */
    public function query(Query $query): void
    {
        if ($query instanceof Query\Select) {
            throw new Exception(
                'Method does not allow select queries, use `select` or `selectOne`',
            );
        }

        $stmt = new Statement($this, $this->profiler, $query);
        $gen = $stmt->execute();

        // consume generator
        $gen->getReturn();
    }

    /**
     * Present a single entity as a simple array
     *
     * @psalm-template TEntityPresenterEntity of Entity
     * @psalm-template TEntityPresenter of EntityPresenter<TEntityPresenterEntity>
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param Entity $entity Entity to present
     * @return array|null
     */
    public function presentEntity(string $presenterKlass, Entity $entity): ?array
    {
        $this->assertValidPresenterClass($presenterKlass);

        $presenter = $this->createPresenter();

        return $presenter->presentEntity($presenterKlass, $entity);
    }

    /**
     * Present collection as a simple array
     *
     * @psalm-template TEntityPresenterEntity of Entity
     * @psalm-template TEntityPresenter of EntityPresenter<TEntityPresenterEntity>
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the collection with
     * @param Collection $collection Collection to present
     * @return array
     */
    public function presentCollection(string $presenterKlass, Collection $collection): array
    {
        return $collection->present($presenterKlass);
    }

    /**
     * Create an presenter instance
     *
     * @return Presenter An presenter instance
     */
    public function createPresenter(): Presenter
    {
        return new Presenter($this);
    }

    /**
     * Create a new empty collection
     *
     * @return Collection Empty collection
     */
    public function createCollection(): Collection
    {
        return new Collection($this);
    }

    /**
     * Check for a valid entity class name
     *
     * @param string $klass Entity class name
     * @throws Exception When entity class name is invalid
     */
    public static function assertValidEntityClass(string $klass): void
    {
        if (!is_subclass_of($klass, Entity::class)) {
            throw new Exception('Invalid entity: ' . $klass);
        }

        if (empty($klass::tableName())) {
            throw new Exception('Invalid table name, can not be empty');
        }
    }

    /**
     * Check for a valid presenter class name
     *
     * @param string $presenterClassName Presenter class name
     * @throws Exception When presenter class name is invalid
     */
    public static function assertValidPresenterClass(string $presenterClassName): void
    {
        if (!is_subclass_of($presenterClassName, EntityPresenter::class)) {
            throw new Exception('Invalid presenter: ' . $presenterClassName);
        }

        if (empty($presenterClassName::getEntityKlass())) {
            throw new Exception('Missing entity klass for presenter: ' . $presenterClassName);
        }
    }

    /**
     * Check for a valid repository class name
     *
     * @param string $repositoryClassName Repository class name
     * @throws Exception When repository class name is invalid
     */
    private static function assertValidRepositoryClass(string $repositoryClassName): void
    {
        if (
            !is_subclass_of($repositoryClassName, Repository::class) &&
            $repositoryClassName !== Repository::class
        ) {
            throw new Exception('Invalid repository: ' . $repositoryClassName);
        }
    }
}
