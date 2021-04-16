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

/**
 * Grouped collection of collections
 *
 * @psalm-template TEntity of \Access\Entity
 * @author Tim <me@justim.net>
 */
class GroupedCollectionIterator implements \Iterator
{
    /**
     * @var array<array-key, Collection<TEntity>> $groups
     */
    private array $groups;

    /**
     * Create a collection iterator
     *
     * @param array<array-key, Collection<TEntity>> $groups
     */
    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * Get the current collection
     *
     * Iterator implementation
     *
     * @return Collection<TEntity>
     */
    public function current()
    {
        return current($this->groups);
    }

    /**
     * Get the current group ID
     *
     * Iterator implementation
     *
     * @return mixed
     * @psalm-return array-key
     */
    public function key()
    {
        return key($this->groups);
    }

    /**
     * Get the current entity's ID
     *
     * Iterator implementation
     *
     * @return void
     */
    public function next()
    {
        next($this->groups);
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
        reset($this->groups);
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
        return key($this->groups) !== null;
    }
}
