<?php

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
 */
class Repository
{
    /**
     * @var Database
     */
    private $db;

    /**
     * @var string Entity class name
     */
    private $klass;

    /**
     * Create a entity repository
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
     * @param array $fields List of fields with values
     * @return ?Entity
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
     * @param array $fields List of fields with values
     * @param ?int $limit A a limit to the query
     * @return \Generator - yields Entity
     */
    public function findBy($fields, int $limit = null): \Generator
    {
        $where = [];
        foreach ($fields as $field => $value) {
            $condition = "{$field} = ?";

            if (strpos($field, '?') !== false) {
                $condition = $field;
            } elseif (is_array($value)) {
                $condition = "{$field} IN (?)";
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
     * @param int[] $ids
     * @param int $limit
     * @return \Generator - yields Entity
     */
    public function findByIds(array $ids, int $limit = null): \Generator
    {
        $fields = [
            'id' => $ids,
        ];

        return $this->findBy($fields, $limit);
    }

    /**
     * Execute a select query
     *
     * @param Query\Select $query Select query to be executed
     * @return \Generator - yields Entity
     */
    public function select(Query\Select $query): \Generator
    {
        return $this->db->select($this->klass, $query);
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

        foreach ($result as $entity) {
            $collection->addEntity($entity);
        }

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
}
