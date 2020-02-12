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
use Access\Collection;
use Access\Database;
use Access\Entity;
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
    private $db;

    /**
     * @psalm-var class-string<TEntity> $klass
     *
     * @var string Entity class name
     */
    private $klass;

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
     * @return ?Entity
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
     */
    public function findOneBy(array $fields): ?Entity
    {
        $gen = $this->findBy($fields, 1);
        $records = iterator_to_array($gen);

        if (empty($records)) {
            return null;
        }

        return current($records);
    }

    /**
     * Find a list of entities by searching for column values
     *
     * @psalm-return \Generator<int, TEntity, mixed, void> - yields Entity
     *
     * @param array<string, mixed> $fields List of fields with values
     * @param ?int $limit A a limit to the query
     * @return \Generator - yields Entity
     */
    public function findBy($fields, int $limit = null): \Generator
    {
        /* @var array<string, mixed> $where */
        $where = [];

        foreach ($fields as $field => $value) {
            $condition = "{$field} = ?";

            if (strpos((string) $field, '?') !== false) {
                $condition = $field;
            } elseif (is_array($value)) {
                if (!empty($value)) {
                    $condition = "{$field} IN (?)";
                } else {
                    // empty collections make no sense...
                    // droppping the whole condition is risky because you may
                    // over-select a whole bunch of records, better is to
                    // under-select.
                    $condition = "1 = 2";
                }
            }

            $where[$condition] = $value;
        }

        $query = new Query\Select($this->klass::tableName());
        $query->where($where);

        if ($limit !== null) {
            $query->limit($limit);
        }

        yield from $this->db->select($this->klass, $query);
    }

    /**
     * Find multiple entities by its ID
     *
     * @psalm-return \Generator<int, TEntity, mixed, void> - yields Entity
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
     * Find all entities (default sort `id ASC`)
     *
     * @psalm-return \Generator<int, TEntity, mixed, void> - yields Entity
     *
     * @param ?int $limit A a limit to the query
     * @param string $orderBy The order to use to find all entities
     * @return \Generator - yields Entity
     */
    public function findAll(?int $limit = null, string $orderBy = 'id ASC'): \Generator
    {
        $query = new Query\Select($this->klass::tableName());

        $query->orderBy($orderBy);

        if ($limit !== null) {
            $query->limit($limit);
        }

        yield from $this->db->select($this->klass, $query);
    }

    /**
     * Execute a select query
     *
     * @psalm-return \Generator<int, TEntity, mixed, void> - yields Entity
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
     */
    public function selectOne(Query\Select $query): ?Entity
    {
        return $this->db->selectOne($this->klass, $query);
    }

    /**
     * Execute a select query in a batched fashion
     *
     * @param Query\Select $query Select query to be executed
     */
    public function selectBatched(Query\Select $query): \Generator
    {
        $batch = new Batch($this->db);

        $result = $this->select($query);

        foreach ($result as $entity) {
            $batch->addEntity($entity);

            if ($batch->isFull()) {
                yield $batch;

                $batch = new Batch($this->db);
            }
        }

        if (!$batch->isEmpty()) {
            yield $batch;
        }

        return $result->getReturn();
    }

    /**
     * Execute a select query with a Collection as result
     *
     * @param Query\Select $query Select query to be executed
     * @return Collection
     */
    public function selectCollection(Query\Select $query): Collection
    {
        $collection = new Collection($this->db);

        $result = $this->select($query);

        $collection->fromIterable($result);

        return $collection;
    }

    /**
     * Create an empty collection
     *
     * @return Collection
     */
    protected function createEmptyCollection(): Collection
    {
        return new Collection($this->db);
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
