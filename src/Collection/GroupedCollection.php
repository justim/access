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
 * @author Tim <me@justim.net>
 */
class GroupedCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @var array<mixed, Collection> $groups
     */
    private $groups;

    /**
     * Create a grouped collection with a indexed list of collections
     *
     * @param array<mixed, Collection> $groups
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
    public function offsetExists($id)
    {
        return isset($this->groups[$id]);
    }

    /**
     * Get a collection by its group ID
     *
     * @param int $id
     * @return ?Collection
     */
    public function offsetGet($id)
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
    public function offsetSet($id, $value)
    {
        throw new Exception('Not possible to add new collections through array access');
    }

    /**
     * Not allowed
     *
     * @throws Exception
     */
    public function offsetUnset($id)
    {
        throw new Exception('Not possible to remove collections through array access');
    }

    /**
     * Get the collection iterator
     *
     * Iterator implementation
     *
     * @return GroupedCollectionIterator
     */
    public function getIterator()
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
    public function count()
    {
        return count($this->groups);
    }
}
