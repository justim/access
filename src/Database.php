<?php

declare(strict_types=1);

namespace Access;

use Access\Entity;
use Access\Exception;
use Access\Profiler;
use Access\Query;
use Access\Repository;
use Access\Statement;
use Access\StatementPool;

/**
 * An Access database
 *
 * Main entry for your database needs
 */
class Database
{
    /**
     * PDO connection
     *
     * @var \PDO $connection
     */
    private $connection = null;

    /**
     * Statement pool
     *
     * @var StatementPool $statementPool
     */
    private $statementPool = null;

    /**
     * Profiler
     *
     * @var Profiler $profiler
     */
    private $profiler = null;

    /**
     * Create a Access database with a PDO connection
     *
     * @param \PDO $connection A PDO connection
     */
    public function __construct(\PDO $connection)
    {
        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->connection = $connection;
        $this->statementPool = new StatementPool($this);
        $this->profiler = new Profiler();
    }

    /**
     * Create a access database with a PDO connection string
     *
     * @param string $connectionString A PDO connection string
     * @return self A Access database object
     */
    public static function create(string $connectionString): self
    {
        try {
            $pdoConnection = new \PDO($connectionString);
            return new self($pdoConnection);
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
     * Get the reposity to find entities
     *
     * @param string $klass Entity class name
     */
    public function getRepository(string $klass): Repository
    {
        $this->assertValidEntityClass($klass);

        $repositoryClassName = $klass::getRepository();

        $this->assertValidRepositoryClass($repositoryClassName);

        return new $repositoryClassName($this, $klass);
    }

    /**
     * Find a single entity by its ID
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
     * @param string $klass Entity class name
     * @param array $fields List of fields with values
     * @return ?Entity
     */
    public function findOneBy(string $klass, array $fields): ?Entity
    {
        return $this->getRepository($klass)->findOneBy($fields);
    }

    /**
     * Find a list of entities by searching for column values
     *
     * @param string $klass Entity class name
     * @param array $fields List of fields with values
     * @param ?int $limit A a limit to the query
     * @return \Generator - yields Entity
     */
    public function findBy(string $klass, $fields, int $limit = null): \Generator
    {
        return $this->getRepository($klass)->findBy($fields, $limit);
    }

    /**
     * Find a list of entities by their ids
     *
     * @param string $klass Entity class name
     * @param int[] $ids List of ides
     * @param ?int $limit A a limit to the query
     * @return \Generator - yields Entity
     */
    public function findByIds(string $klass, array $ids, int $limit = null): \Generator
    {
        return $this->getRepository($klass)->findByIds($ids, $limit);
    }

    /**
     * Execute a select query
     *
     * @param string $klass Entity class name
     * @param Query\Select $query Select query to be executed
     * @return \Generator - yields Entity
     */
    public function select(string $klass, Query\Select $query): \Generator
    {
        $this->assertValidEntityClass($klass);

        $stmt = new Statement($this, $this->profiler, $query);

        foreach ($stmt->execute() as $record) {
            $model = new $klass();
            $model->hydrate($record);
            yield $model->getId() => $model;
        }
    }

    /**
     * Execute a select query and return the first entity
     *
     * @param string $klass Entity class name
     * @param Query\Select $query Select query to be executed
     * @return ?Entity
     */
    public function selectOne(string $klass, Query\Select $query): ?Entity
    {
        $query->limit(1);

        $records = iterator_to_array($this->select($klass, $query));

        if (empty($records)) {
            return null;
        }

        return current($records);
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
            throw new Exception('Method does not allow select queries, use `select` or `selectOne`');
        }

        $stmt = new Statement($this, $this->profiler, $query);
        $gen = $stmt->execute();

        // consume generator
        $gen->getReturn();
    }

    /**
     * Check for a valid entity class name
     *
     * @param string $klass Entity class name
     * @throws Exception When entity class name is invalid
     */
    public function assertValidEntityClass(string $klass): void
    {
        if (!is_subclass_of($klass, Entity::class)) {
            throw new Exception('Invalid entity');
        }

        if (empty($klass::tableName())) {
            throw new Exception('Invalid table name');
        }
    }

    /**
     * Check for a valid repository class name
     *
     * @param string $repositoryClassName Repository class name
     * @throws Exception When repository class name is invalid
     */
    private function assertValidRepositoryClass(string $repositoryClassName): void
    {
        if (!is_subclass_of($repositoryClassName, Repository::class) &&
            $repositoryClassName !== Repository::class
        ) {
            throw new Exception('Invalid repository');
        }
    }
}
