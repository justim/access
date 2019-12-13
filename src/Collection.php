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

use Access\Database;
use Access\Collection\Iterator;
use Access\Entity;
use Access\Exception;

/**
 * Collection of entities
 *
 * @psalm-template TEntity of Entity
 * @author Tim <me@justim.net>
 */
class Collection implements \ArrayAccess, \Countable, \IteratorAggregate
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
     * Create a entity batch
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param iterable<Entity> $iterable List of entities
     */
    public function fromIterable(iterable $iterable): void
    {
        foreach ($iterable as $entity) {
            $this->addEntity($entity);
        }
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
     * @psalm-param class-string<TEntity> $klass
     *
     * @param string $klass Entity class name
     * @param callable $mapper
     * @return Collection
     */
    public function findRefs(string $klass, callable $mapper): Collection
    {
        $ids = [];

        foreach ($this->entities as $entity) {
            /** @var int|null $id */
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
     * Merge a collection into this collection
     *
     * @param Collection $source Collection to merge
     */
    public function merge(Collection $source): void
    {
        $ids = $this->getIds();

        foreach ($source as $entity) {
            if (!in_array($entity->getId(), $ids)) {
                $this->addEntity($entity);
            }
        }
    }

    /**
     * Group collection by a specified index
     *
     * @param callable $groupIndexMapper Should return the index of the group
     * @return array<mixed, Collection>
     */
    public function groupBy(callable $groupIndexMapper): array
    {
        /** @var array<mixed, Collection> $result */
        $result = [];

        foreach ($this->entities as $entity) {
            $groupIndex = $groupIndexMapper($entity);

            if (!isset($result[$groupIndex])) {
                $result[$groupIndex] = new self($this->db);
            }

            $result[$groupIndex]->addEntity($entity);
        }

        return $result;
    }

    /**
     * Sort collection
     *
     * NOTE: uses `usort` with $comparer as compare function
     *
     * @param callable $comparer Function to sort/compare with
     */
    public function sort(callable $comparer): void
    {
        usort($this->entities, $comparer);
    }

    /**
     * Map over collection
     *
     * @param callable $mapper Function to call for every entity
     * @return mixed[]
     */
    public function map(callable $mapper): array
    {
        $result = [];

        foreach ($this->entities as $entity) {
            $value = $mapper($entity);

            $result[] = $value;
        }

        return $result;
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
     * Get the collection iterator
     *
     * Iterator implementation
     *
     * @return Iterator
     */
    public function getIterator()
    {
        return new Iterator($this->entities);
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
