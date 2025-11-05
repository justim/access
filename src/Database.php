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

use Access\Cascade\CascadeDeleteResolver;
use Access\Driver\DriverInterface;
use Access\Driver\Mysql;
use Access\Driver\Sqlite;
use Access\Exception\ClosedConnectionException;
use Access\Presenter\EntityPresenter;
use Access\Query\IncludeSoftDeletedFilter;
use DateTimeImmutable;
use Generator;
use PDO;
use Psr\Clock\ClockInterface;
use ReflectionException;
use ReflectionMethod;

/**
 * An Access database
 *
 * Main entry for your database needs
 *
 * @author Tim <me@justim.net>
 */
class Database
{
    private ?PDO $connection;

    private DriverInterface $driver;

    private StatementPool $statementPool;

    private ProfilerInterface $profiler;

    /**
     * Clock used for the timestamps
     */
    private ClockInterface $clock;

    /**
     * Include soft deleted items in the query
     *
     * Initially set to `Auto`, will follow the setting of the query
     */
    private IncludeSoftDeletedFilter $includeSoftDeletedFilter = IncludeSoftDeletedFilter::Auto;

    /**
     * Create an Access database with a PDO connection
     *
     * @param PDO $connection A PDO connection
     * @param ?ProfilerInterface $profiler A custom profiler, optionally
     */
    public function __construct(
        PDO $connection,
        ?ProfilerInterface $profiler = null,
        ?ClockInterface $clock = null,
    ) {
        $this->statementPool = new StatementPool($this);
        $this->profiler = $profiler ?? new Profiler();
        $this->clock = $clock ?? new InternalClock();

        $this->setConnection($connection);
    }

    /**
     * Create an access database with a PDO connection string
     *
     * @param string $connectionString A PDO connection string
     * @param ?ProfilerInterface $profiler A custom profiler, optionally
     * @return self An Access database object
     */
    public static function create(
        string $connectionString,
        ?ProfilerInterface $profiler = null,
        ?ClockInterface $clock = null,
    ): self {
        try {
            $connection = new PDO($connectionString);
            return new self($connection, $profiler, $clock);
        } catch (\Exception $e) {
            throw new Exception("Invalid database: {$connectionString}", 0, $e);
        }
    }

    /**
     * Get the PDO connection
     *
     * @return PDO A PDO connection
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            throw new ClosedConnectionException();
        }

        return $this->connection;
    }

    /**
     * Close the PDO connection by setting the property to null
     *
     * Note that in order for the connection to be closed, all it's instances
     * must be set to null
     */
    public function closeConnection(): void
    {
        $this->connection = null;
        $this->statementPool->clear();
    }

    /**
     * Set a new PDO connection
     *
     * @param PDO $connection A new PDO connection
     */
    final public function setConnection(PDO $connection): void
    {
        // make sure we don't have any link to the old connection
        $this->statementPool->clear();

        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection = $connection;

        /** @var string $driverName */
        $driverName = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->driver = match ($driverName) {
            Mysql::NAME => new Mysql(),
            Sqlite::NAME => new Sqlite(),
            default => throw new Exception("Unsupported driver: {$driverName}"),
        };
    }

    public function getStatementPool(): StatementPool
    {
        return $this->statementPool;
    }

    /**
     * Get the profiler for some timings
     */
    public function getProfiler(): ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * Get the current driver of the connection
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Get the given driver, or an arbitrary default
     *
     * The default is `Mysql`.
     *
     * This method should be temporary, in a future version the methods
     * accepting a driver will have their argument made non-nullable.
     */
    public static function getDriverOrDefault(?DriverInterface $driver): DriverInterface
    {
        return $driver ?? new Mysql();
    }

    public function beginTransaction(): Transaction
    {
        $transaction = new Transaction($this);
        $transaction->begin();

        return $transaction;
    }

    /**
     * Create a lock object
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
        self::assertValidEntityClass($klass);

        $repositoryClassName = $klass::getRepository();

        self::assertValidRepositoryClass($repositoryClassName);

        /** @var Repository<TEntity> $repository
         * @psalm-suppress UnsafeInstantiation */
        $repository = new $repositoryClassName($this, $klass);

        return $repository->withIncludeSoftDeleted($this->includeSoftDeletedFilter);
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
     * @psalm-return Generator<int, TEntity, mixed, void> - yields Entity
     *
     * @param string $klass Entity class name
     * @param array<string, mixed> $fields List of fields with values
     * @param ?int $limit A limit to the query
     * @return Generator - yields Entity
     */
    public function findBy(string $klass, array $fields, ?int $limit = null): Generator
    {
        yield from $this->getRepository($klass)->findBy($fields, $limit);
    }

    /**
     * Find a list of entities by their ids
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-suppress InvalidReturnType TODO remove when psalm supports this
     * @psalm-return Generator<int, TEntity, mixed, void> - yields Entity
     *
     * @param string $klass Entity class name
     * @param int[] $ids List of ids
     * @param ?int $limit A limit to the query
     * @return Generator - yields Entity
     */
    public function findByIds(string $klass, array $ids, ?int $limit = null): Generator
    {
        yield from $this->getRepository($klass)->findByIds($ids, $limit);
    }

    /**
     * Find all entities (default sort `id ASC`)
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-suppress InvalidReturnType TODO remove when psalm supports this
     * @psalm-return Generator<int, TEntity, mixed, void> - yields Entity
     *
     * @param string $klass Entity class name
     * @param ?int $limit A limit to the query
     * @param string $orderBy The order to use to find all entities
     * @return Generator - yields Entity
     */
    public function findAll(
        string $klass,
        ?int $limit = null,
        string $orderBy = 'id ASC',
    ): Generator {
        yield from $this->getRepository($klass)->findAll($limit, $orderBy);
    }

    /**
     * Execute a select query with a entity provider
     *
     * @psalm-template TEntity of Entity
     * @psalm-return Generator<int|null, TEntity, mixed, void> - yields Entity
     *
     * @param EntityProvider<TEntity> $entityProvider Creator the empty entity shells
     * @param Query\Select $query Select query to be executed
     * @return Generator - yields Entity
     */
    public function selectWithEntityProvider(
        EntityProvider $entityProvider,
        Query\Select $query,
    ): Generator {
        $oldIncludeSoftDeleted = $query->setIncludeSoftDeleted($this->includeSoftDeletedFilter);

        try {
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
        } finally {
            $query->setIncludeSoftDeleted($oldIncludeSoftDeleted);
        }
    }

    /**
     * Execute a select query
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $klass
     * @psalm-return Generator<int|null, TEntity, mixed, void> - yields Entity
     *
     * @param string $klass Entity class name
     * @param Query\Select $query Select query to be executed
     * @return Generator - yields Entity
     */
    public function select(string $klass, Query\Select $query): Generator
    {
        self::assertValidEntityClass($klass);

        $entityProvider = new EntityProvider($klass);

        return $this->selectWithEntityProvider($entityProvider, $query);
    }

    /**
     * Execute a select query and return the first entity
     *
     * @param string $klass Entity class name
     * @param Query\Select $query Select query to be executed
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
     */
    public function insert(Entity $model): Entity
    {
        self::assertValidEntityClass(get_class($model));

        $values = $model->getInsertValues($this->clock);

        $query = new Query\Insert($model::tableName());
        $query->values($values);

        $stmt = new Statement($this, $this->profiler, $query);
        $gen = $stmt->execute();
        $model->setId((int)$gen->getReturn());

        // set default values/timestamps
        $model->markUpdated($values);

        return $model;
    }

    /**
     * Send changes in model to database
     *
     * @return bool Was something actually updated
     */
    public function update(Entity $model): bool
    {
        self::assertValidEntityClass(get_class($model));

        $id = $model->getId();
        $values = $model->getUpdateValues($this->clock);

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
     * A transaction is started and the model is deleted
     *
     * @param Entity $model Model to delete
     * @return bool Was something actually deleted
     */
    public function delete(Entity $model): bool
    {
        self::assertValidEntityClass(get_class($model));

        $transaction = $this->beginTransaction();

        try {
            $resolver = new CascadeDeleteResolver($this, $model, DeleteKind::Regular);
            $updated = $resolver->execute();

            $transaction->commit();

            return $updated;
        } catch (\Exception $e) {
            $transaction->rollBack();

            // just pass on our own exceptions
            if ($e instanceof Exception) {
                throw $e;
            }

            throw new Exception('Entity is not deletable', 0, $e);
        }
    }

    /**
     * Mark the entity as soft-deleted and save to database
     *
     * A transaction is started and the model is soft deleted
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
            $setDeletedAt = new ReflectionMethod($model, 'setDeletedAt');
        } catch (ReflectionException $e) {
            throw new Exception('Entity is not soft deletable', 0, $e);
        }

        if (!$setDeletedAt->isPublic()) {
            throw new Exception('Soft delete method is not public');
        }

        $transaction = $this->beginTransaction();

        try {
            $resolver = new CascadeDeleteResolver($this, $model, DeleteKind::Soft);
            $updated = $resolver->execute();

            $transaction->commit();

            return $updated;
        } catch (\Exception $e) {
            $transaction->rollBack();

            // just pass on our own exceptions
            if ($e instanceof Exception) {
                throw $e;
            }

            throw new Exception('Entity is not soft deletable', 0, $e);
        }
    }

    /**
     * Execute a raw query
     *
     * Has no return value, not suited for select queries
     *
     * @throws Exception when $query is a Query\Select
     */
    public function query(Query $query): void
    {
        if ($query instanceof Query\Select) {
            throw new Exception(
                'Method does not allow select queries, use `select` or `selectOne`',
            );
        }

        $oldIncludeSoftDeleted = $query->setIncludeSoftDeleted($this->includeSoftDeletedFilter);

        try {
            $stmt = new Statement($this, $this->profiler, $query);
            $gen = $stmt->execute();

            // consume generator
            $gen->getReturn();
        } finally {
            $query->setIncludeSoftDeleted($oldIncludeSoftDeleted);
        }
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
     */
    public function presentEntity(string $presenterKlass, Entity $entity): ?array
    {
        self::assertValidPresenterClass($presenterKlass);

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
     */
    public function presentCollection(string $presenterKlass, Collection $collection): array
    {
        return $collection->present($presenterKlass);
    }

    /**
     * Create a presenter instance
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
     * @psalm-template TEntity of Entity
     *
     * @return Collection Empty collection
     * @param iterable<Entity>|null $iterable List of entities
     * @psalm-param iterable<TEntity>|null $iterable List of entities
     * @psalm-return ($iterable is null ? Collection : Collection<TEntity>)
     */
    public function createCollection(?iterable $iterable = null): Collection
    {
        return new Collection($this, $iterable);
    }

    /**
     * Returns the current time as a DateTimeImmutable Object
     */
    public function now(): DateTimeImmutable
    {
        return $this->clock->now();
    }

    /**
     * Create database instance with a different includeSoftDeleted setting
     *
     * All queries/join that are used by this query will also include soft deleted
     *
     * Initially set to `null`, will follow the setting of the query
     *
     * @return static Version of the database instance with the new setting
     */
    public function withIncludeSoftDeleted(bool $includeSoftDeleted): static
    {
        $self = clone $this;
        $self->includeSoftDeletedFilter = $includeSoftDeleted
            ? IncludeSoftDeletedFilter::Include
            : IncludeSoftDeletedFilter::Exclude;

        return $self;
    }

    /**
     * Check for a valid entity class name
     *
     * @param string $klass Entity class name
     * @throws Exception When entity class name is invalid
     * @psalm-assert class-string<Entity> $klass
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
     * @psalm-assert class-string<EntityPresenter> $presenterClassName
     */
    public static function assertValidPresenterClass(string $presenterClassName): void
    {
        if (!is_subclass_of($presenterClassName, EntityPresenter::class)) {
            throw new Exception('Invalid presenter: ' . $presenterClassName);
        }

        /**
         * It's an assertion
         * @psalm-suppress TypeDoesNotContainType
         */
        if (empty($presenterClassName::getEntityKlass())) {
            throw new Exception('Missing entity klass for presenter: ' . $presenterClassName);
        }
    }

    /**
     * Check for a valid repository class name
     *
     * @param string $repositoryClassName Repository class name
     * @throws Exception When repository class name is invalid
     * @psalm-assert class-string<Repository> $repositoryClassName
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
