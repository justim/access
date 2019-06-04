<?php

declare(strict_types=1);

namespace Access;

use Access\Database;
use Access\Entity;
use Access\Exception;

/**
 * Collection of entities
 */
class Collection implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var Database
     */
    private $db;

    /**
     * @var Entity[] $entities
     */
    private $entities = [];

    /**
     * @var int $iteratorIndex
     */
    private $iteratorIndex = 0;

    /**
     * Create a entity batch
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Add a entity to the collection
     *
     * @param Entity $entity
     */
    public function addEntity(Entity $entity): void
    {
        $this->entities[] = $entity;
    }

    /**
     * Is this collection empty?
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->entities) === 0;
    }

    /**
     * Get a list of the ids of the entities
     *
     * @return int[]
     */
    public function getIds(): array
    {
        return array_map(function (Entity $entity) {
            return $entity->getId();
        }, $this->entities);
    }

    /**
     * Find the refs in a collection
     *
     * @param string $klass Entity class name
     * @param callable $mapper
     * @return Collection
     */
    public function findRefs(string $klass, callable $mapper): Collection
    {
        $ids = [];

        foreach ($this->entities as $entity) {
            $id = $mapper($entity);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        $result = new self($this->db);

        if (empty($ids)) {
            return $result;
        }

        $ids = array_unique($ids);
        $refs = $this->db->findByIds($klass, $ids);

        foreach ($refs as $ref) {
            $result->addEntity($ref);
        }

        return $result;
    }

    /**
     * Find the first entity in a collection
     *
     * @param callable $finder Return entity when $finder returns `true`
     * @return ?Entity
     */
    public function find(callable $finder): ?Entity
    {
        foreach ($this->entities as $entity) {
            if ($finder($entity)) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * Does the ID exist?
     *
     * @param int $id
     * @return bool
     */
    public function offsetExists($id)
    {
        return $this->offsetGet($id) !== null;
    }

    /**
     * Get a entity by its ID from the collection
     *
     * @param int $id
     * @return ?Entity
     */
    public function offsetGet($id)
    {
        foreach ($this->entities as $entity) {
            if ($entity->getId() === $id) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * Not allowed
     *
     * @throws Exception
     */
    public function offsetSet($id, $value)
    {
        throw new Exception('Not possible to add new entities through array access');
    }

    /**
     * Not allowed
     *
     * @throws Exception
     */
    public function offsetUnset($id)
    {
        throw new Exception('Not possible to remove entities through array access');
    }

    /**
     * Get the current entity
     *
     * Iterator implementation
     *
     * @return Entity
     */
    public function current()
    {
        return $this->entities[$this->iteratorIndex];
    }

    /**
     * Get the current entity's ID
     *
     * Iterator implementation
     *
     * @return int
     */
    public function key()
    {
        $entity = $this->entities[$this->iteratorIndex];

        return $entity->getId();
    }

    /**
     * Get the current entity's ID
     *
     * Iterator implementation
     *
     * @return int
     */
    public function next()
    {
        $this->iteratorIndex++;
    }

    /**
     * Reset interator pointer
     *
     * Iterator implementation
     *
     * @return void
     */
    public function rewind()
    {
        $this->iteratorIndex = 0;
    }

    /**
     * Is the current interator pointer valid?
     *
     * Iterator implementation
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->entities[$this->iteratorIndex]);
    }

    /**
     * Get the number of entities in collection
     *
     * \Countable implementation
     *
     * @return int
     */
    public function count()
    {
        return count($this->entities);
    }
}
