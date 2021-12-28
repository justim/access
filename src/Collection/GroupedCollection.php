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

namespace Access\Collection;

use Access\Collection;
use Access\Collection\GroupedCollectionIterator;
use Access\Exception;

/**
 * Grouped collections of entities
 *
 * @psalm-template TEntity of \Access\Entity
 * @author Tim <me@justim.net>
 */
class GroupedCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @var array<array-key, Collection<TEntity>> $groups
     */
    private array $groups;

    /**
     * Create a grouped collection with a indexed list of collections
     *
     * @param array<array-key, Collection<TEntity>> $groups
     */
    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * Does the group ID exist?
     *
     * @param int $id
     * @return bool
     */
    public function offsetExists(mixed $id): bool
    {
        return isset($this->groups[$id]);
    }

    /**
     * Get a collection by its group ID
     *
     * @param int $id
     * @return ?Collection<TEntity>
     */
    public function offsetGet(mixed $id): ?Collection
    {
        if (!$this->offsetExists($id)) {
            return null;
        }

        return $this->groups[$id];
    }

    /**
     * Not allowed
     *
     * @throws Exception
     */
    public function offsetSet(mixed $id, mixed $value): void
    {
        throw new Exception('Not possible to add new collections through array access');
    }

    /**
     * Not allowed
     *
     * @throws Exception
     */
    public function offsetUnset(mixed $id): void
    {
        throw new Exception('Not possible to remove collections through array access');
    }

    /**
     * Get the collection iterator
     *
     * Iterator implementation
     *
     * @return GroupedCollectionIterator<TEntity>
     */
    public function getIterator(): \Traversable
    {
        return new GroupedCollectionIterator($this->groups);
    }

    /**
     * Get the number of collections
     *
     * \Countable implementation
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->groups);
    }
}
