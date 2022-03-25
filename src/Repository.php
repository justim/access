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

use Access\Batch;
use Access\Clause\Condition\Equals;
use Access\Clause\Condition\In;
use Access\Clause\Condition\Raw;
use Access\Collection;
use Access\Database;
use Access\Entity;
use Access\EntityProvider\VirtualFieldEntity;
use Access\EntityProvider\VirtualFieldEntityProvider;
use Access\Query;

/**
 * Entity repository
 *
 * Collection of methods to easily find entities
 *
 * @psalm-template TEntity of Entity
 * @author Tim <me@justim.net>
 */
class Repository
{
    /**
     * @var Database
     */
    private Database $db;

    /**
     * @psalm-var class-string<TEntity> $klass
     *
     * @var string Entity class name
     */
    private string $klass;

    /**
     * Create a entity repository
     *
     * @psalm-param class-string<TEntity> $klass
     *
     * @param Database $db
     * @param string $klass Entity class name
     */
    public function __construct(Database $db, string $klass)
    {
        $db->assertValidEntityClass($klass);

        $this->db = $db;
        $this->klass = $klass;
    }

    /**
     * Find a single entity by its ID
     *
     * @param int $id ID of the entity
     * @psalm-return ?TEntity
     */
    public function findOne(int $id): ?Entity
    {
        return $this->findOneBy([
            'id' => $id,
        ]);
    }

    /**
     * Find a single entity by searching for column values
     *
     * @param array<string, mixed> $fields List of fields with values
     * @return Entity|null
     * @psalm-return ?TEntity
     */
    public function findOneBy(array $fields): ?Entity
    {
        $gen = $this->findBy($fields, 1);
        $records = iterator_to_array($gen, false);

        if (empty($records)) {
            return null;
        }

        return current($records);
    }

    /**
     * Find a list of entities by searching for column values
     *
     * @psalm-return \Generator<int|null, TEntity, mixed, void> - yields Entity
     *
     * @param array<string, mixed> $fields List of fields with values
     * @param ?int $limit A a limit to the query
     * @return \Generator - yields Entity
     */
    public function findBy(array $fields, int $limit = null): \Generator
    {
        $query = new Query\Select($this->klass);

        /** @var mixed $value */
        foreach ($fields as $field => $value) {
            /** @psalm-suppress RedundantCastGivenDocblockType */
            if (strpos((string) $field, '?') !== false) {
                $query->where(new Raw($field, $value));
            } elseif (is_array($value) || $value instanceof Collection) {
                $query->where(new In($field, $value));
            } else {
                $query->where(new Equals($field, $value));
            }
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        yield from $this->db->select($this->klass, $query);
    }

    /**
     * Find a list of entities by searching for column values as collection
     *
     * @param array<string, mixed> $fields List of fields with values
     * @param ?int $limit A a limit to the query
     * @return Collection Collection with `Entity`s
     * @psalm-return Collection<TEntity> Collection with `Entity`s
     */
    public function findByAsCollection(array $fields, int $limit = null): Collection
    {
        $iterator = $this->findBy($fields, $limit);

        /** @var Collection<TEntity> $collection */
        $collection = new Collection($this->db);
        $collection->fromIterable($iterator);

        return $collection;
    }

    /**
     * Find multiple entities by its ID
     *
     * @psalm-return \Generator<int|null, TEntity, mixed, void> - yields Entity
     *
     * @param int[] $ids
     * @param int $limit
     * @return \Generator - yields Entity
     */
    public function findByIds(array $ids, int $limit = null): \Generator
    {
        $fields = [
            'id' => $ids,
        ];

        yield from $this->findBy($fields, $limit);
    }

    /**
     * Find multiple entities by its ID as a collection
     *
     * @param int[] $ids
     * @param int $limit
     * @return Collection Collection with `Entity`s
     */
    public function findByIdsAsCollection(array $ids, int $limit = null): Collection
    {
        $iterator = $this->findByIds($ids, $limit);

        $collection = new Collection($this->db);
        $collection->fromIterable($iterator);

        return $collection;
    }

    /**
     * Find all entities (default sort `id ASC`)
     *
     * @psalm-return \Generator<int|null, TEntity, mixed, void> - yields Entity
     *
     * @param ?int $limit A a limit to the query
     * @param string $orderBy The order to use to find all entities
     * @return \Generator - yields Entity
     */
    public function findAll(?int $limit = null, string $orderBy = 'id ASC'): \Generator
    {
        $query = new Query\Select($this->klass);

        $query->orderBy($orderBy);

        if ($limit !== null) {
            $query->limit($limit);
        }

        yield from $this->db->select($this->klass, $query);
    }

    /**
     * Find all entities as a collection (default sort `id ASC`)
     *
     * @param ?int $limit A a limit to the query
     * @param string $orderBy The order to use to find all entities
     * @return Collection Collection with `Entity`s
     * @psalm-return Collection<TEntity> Collection with `Entity`s
     */
    public function findAllCollection(?int $limit = null, string $orderBy = 'id ASC'): Collection
    {
        $iterator = $this->findAll($limit, $orderBy);

        /** @var Collection<TEntity> $collection */
        $collection = new Collection($this->db);
        $collection->fromIterable($iterator);

        return $collection;
    }

    /**
     * Execute a select query
     *
     * @psalm-return \Generator<int|null, TEntity, mixed, void> - yields Entity
     *
     * @param Query\Select $query Select query to be executed
     * @return \Generator - yields Entity
     */
    public function select(Query\Select $query): \Generator
    {
        yield from $this->db->select($this->klass, $query);
    }

    /**
     * Execute a select query and return the first entity
     *
     * @param Query\Select $query Select query to be executed
     * @return ?Entity
     * @psalm-return ?TEntity
     */
    public function selectOne(Query\Select $query): ?Entity
    {
        return $this->db->selectOne($this->klass, $query);
    }

    /**
     * Execute a select query in a batched fashion
     *
     * @psalm-return \Generator<int, Batch<TEntity>, mixed, void> - yields Batches
     *
     * @param Query\Select $query Select query to be executed
     * @param int|null $batchSize Size of the batches
     */
    public function selectBatched(Query\Select $query, int $batchSize = null): \Generator
    {
        /** @var Batch<TEntity> $batch */
        $batch = new Batch($this->db);

        $result = $this->select($query);

        foreach ($result as $entity) {
            $batch->addEntity($entity);

            if ($batch->isFull($batchSize)) {
                yield $batch;

                /** @var Batch<TEntity> $batch */
                $batch = new Batch($this->db);
            }
        }

        if (!$batch->isEmpty()) {
            yield $batch;
        }
    }

    /**
     * Execute a select query with a Collection as result
     *
     * @param Query\Select $query Select query to be executed
     * @return Collection Collection with `Entity`s
     * @psalm-return Collection<TEntity> Collection with `Entity`s
     */
    public function selectCollection(Query\Select $query): Collection
    {
        /** @var Collection<TEntity> $collection */
        $collection = new Collection($this->db);

        $result = $this->select($query);

        $collection->fromIterable($result);

        return $collection;
    }

    /**
     * Select a virtual field in a query
     *
     * Useful to only fetch counts
     *
     * @psalm-return \Generator<int|null, mixed, mixed, void> - yields Entity
     *
     * @param Query\Select $query Select query to be executed
     * @param string $virtualFieldName Field name to return
     * @return \Generator
     */
    public function selectVirtualField(
        Query\Select $query,
        string $virtualFieldName,
        string $virtualType = null,
    ): \Generator {
        $entityProvider = new VirtualFieldEntityProvider($virtualFieldName, $virtualType);

        $entities = $this->db->selectWithEntityProvider($entityProvider, $query);

        /** @var VirtualFieldEntity $entity */
        foreach ($entities as $id => $entity) {
            yield $id => $entity->getVirtualField();
        }
    }

    /**
     * Select one virtual field in a query
     *
     * Useful to only fetch counts
     *
     * @param Query\Select $query Select query to be executed
     * @param string $virtualFieldName Field name to return
     * @param string $virtualType Type of the virtual field
     * @return mixed
     */
    public function selectOneVirtualField(
        Query\Select $query,
        string $virtualFieldName,
        string $virtualType = null,
    ) {
        $query->limit(1);

        $gen = $this->selectVirtualField($query, $virtualFieldName, $virtualType);

        $records = iterator_to_array($gen, false);

        if (!empty($records)) {
            return $records[0];
        }

        return null;
    }

    /**
     * Select with an entity provider
     *
     * @psalm-return \Generator<int|null, Entity, mixed, void> - yields Entity
     *
     * @param Query\Select $query Select query to be executed
     * @param EntityProvider $entityProvider Entity provider to create base entities
     * @return \Generator Generator with entities
     */
    public function selectWithEntityProvider(
        Query\Select $query,
        EntityProvider $entityProvider,
    ): \Generator {
        $entities = $this->db->selectWithEntityProvider($entityProvider, $query);

        foreach ($entities as $id => $entity) {
            yield $id => $entity;
        }
    }

    /**
     * Select with an entity provider
     *
     * @param Query\Select $query Select query to be executed
     * @param EntityProvider $entityProvider Entity provider to create base entities
     * @return Collection Collection with entities
     */
    public function selectWithEntityProviderCollection(
        Query\Select $query,
        EntityProvider $entityProvider,
    ): Collection {
        $entities = $this->selectWithEntityProvider($query, $entityProvider);

        $collection = $this->db->createCollection();
        $collection->fromIterable($entities);

        return $collection;
    }

    /**
     * Save a model to the database
     *
     * Delegates to insert when no id is available, update otherwise
     *
     * @param Entity $model
     */
    public function save(Entity $entity): void
    {
        $this->db->save($entity);
    }

    /**
     * Create an empty collection
     *
     * @return Collection<TEntity>
     */
    protected function createEmptyCollection(): Collection
    {
        /** @var Collection<TEntity> $emptyCollection */
        $emptyCollection = new Collection($this->db);
        return $emptyCollection;
    }

    /**
     * Execute a raw query
     *
     * Has no return value, not suited for select queries
     *
     * @param Query $query
     * @throws Exception when $query is a Query\Select
     */
    protected function query(Query $query): void
    {
        $this->db->query($query);
    }
}
