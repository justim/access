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

use Access\Collection\GroupedCollection;
use Access\Collection\Iterator;
use Access\Database;
use Access\Entity;
use Access\Exception;
use Access\Presenter;

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
    private Database $db;

    /**
     * @var Entity[] $entities
     * @psalm-var list<TEntity> $entities
     */
    private array $entities = [];

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
     * @psalm-param iterable<TEntity> $iterable List of entities
     */
    public function fromIterable(iterable $iterable): void
    {
        if (is_array($iterable)) {
            $entities = array_values($iterable);
        } else {
            $entities = iterator_to_array($iterable, false);
        }

        $this->entities = array_merge($this->entities, $entities);
    }

    /**
     * Add a entity to the collection
     *
     * @param Entity $entity
     * @psalm-param TEntity $entity
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
     * @return int[] IDs of all entities in collection
     */
    public function getIds(): array
    {
        return $this->map(fn(Entity $entity) => $entity->getId());
    }

    /**
     * Find the refs in a collection
     *
     * Create a new collection of given entity name with an ID equal to the
     * result of the ID mapper
     *
     * @psalm-template TRefEntity of Entity
     * @psalm-param class-string<TRefEntity> $klass
     *
     * @param string $klass Entity class name
     * @param callable $mapper ID mapper, ID of the targeted entity
     * @return Collection<TRefEntity>
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

        /** @var self<TRefEntity> $result */
        $result = new self($this->db);

        if (empty($ids)) {
            return $result;
        }

        $ids = array_unique($ids);
        $refs = $this->db->findByIds($klass, $ids);

        $result->fromIterable($refs);

        return $result;
    }

    /**
     * Find the inversed refs in a collection
     *
     * Create a new collection of given entity name that have a value for the
     * column name equal to the ID of the entities in this collection
     *
     * @psalm-template TRefEntity of Entity
     * @psalm-param class-string<TRefEntity> $klass
     *
     * @param string $klass Entity class name
     * @param string $fieldName Name of the field you want to search
     * @return Collection<TRefEntity>
     */
    public function findInversedRefs(string $klass, string $fieldName): Collection
    {
        $this->db->assertValidEntityClass($klass);

        $validFieldNames = array_keys($klass::fields());

        if (!in_array($fieldName, $validFieldNames, true)) {
            throw new Exception('Unknown field name for inversed refs');
        }

        /** @var self<TRefEntity> $result */
        $result = new self($this->db);

        if ($this->isEmpty()) {
            return $result;
        }

        $refs = $this->db->findBy($klass, [
            $fieldName => $this,
        ]);

        $result->fromIterable($refs);

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
     * Get the first entity of this collection
     *
     * @return Entity|null
     */
    public function first(): ?Entity
    {
        if (isset($this->entities[0])) {
            return $this->entities[0];
        }

        return null;
    }

    /**
     * Merge a collection into this collection
     *
     * @param Collection<Entity> $source Collection to merge
     * @psalm-param Collection<TEntity> $source Collection to merge
     */
    public function merge(Collection $source): void
    {
        $ids = $this->getIds();

        foreach ($source as $entity) {
            if (!in_array($entity->getId(), $ids, true)) {
                $this->addEntity($entity);
            }
        }
    }

    /**
     * Group collection by a specified index
     *
     * @psalm-param callable(Entity): array-key $groupIndexMapper
     * @param callable $groupIndexMapper Should return the index of the group
     * @return GroupedCollection
     */
    public function groupBy(callable $groupIndexMapper): GroupedCollection
    {
        /** @var array<array-key, self<TEntity>> $groups */
        $groups = [];

        foreach ($this->entities as $entity) {
            $groupIndex = $groupIndexMapper($entity);

            if (!isset($groups[$groupIndex])) {
                /** @var self<TEntity> $collection */
                $collection = new self($this->db);
                $groups[$groupIndex] = $collection;
            }

            $groups[$groupIndex]->addEntity($entity);
        }

        return new GroupedCollection($groups);
    }

    /**
     * Sort collection
     *
     * NOTE: uses `usort` with $comparer as compare function
     *
     * @psalm-param callable(mixed, mixed): int $comparer
     * @param callable $comparer Function to sort/compare with
     * @return $this
     */
    public function sort(callable $comparer): self
    {
        usort($this->entities, $comparer);

        return $this;
    }

    /**
     * Map over collection
     *
     * @psalm-template T
     * @psalm-param callable(Entity): T $mapper
     * @psalm-return T[]
     * @param callable $mapper Function to call for every entity
     * @return mixed[] Your mapped values
     */
    public function map(callable $mapper): array
    {
        return array_map($mapper, $this->entities);
    }

    /**
     * Reduce collection to something different
     *
     * @psalm-param callable(mixed, Entity): mixed $reducer
     * @param callable $reducer Function to call for every entity
     * @param mixed $initial Initial (or final on empty collection) value
     * @return mixed The reduced result
     */
    public function reduce(callable $reducer, $initial = null)
    {
        return array_reduce($this->entities, $reducer, $initial);
    }

    /**
     * Create a new filtered collection
     *
     * @psalm-param callable(mixed, mixed=):scalar $finder
     * @param callable $finder Include entity when $finder returns `true`
     * @return Collection<TEntity> Newly created, and filtered, collection
     */
    public function filter(callable $finder): Collection
    {
        /** @var self<TEntity> $result */
        $result = new self($this->db);
        $result->fromIterable(array_filter($this->entities, $finder));

        return $result;
    }

    /**
     * Present collection as a simple array
     *
     * @psalm-template TEntityPresenter of Presenter\EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the collection with
     * @return array
     */
    public function present(string $presenterKlass): array
    {
        $presenter = new Presenter($this->db);

        return $presenter->presentCollection($presenterKlass, $this);
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
     * @psalm-return Iterator<TEntity>
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
