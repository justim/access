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

use Access\Entity;

/**
 * Collection of entities
 *
 * @author Tim <me@justim.net>
 */
class Iterator implements \Iterator
{
    /**
     * @var Entity[] $entities
     */
    private $entities;

    /**
     * Create a collection iterator
     *
     * @param Entity[] $entities
     */
    public function __construct(array $entities)
    {
        $this->entities = $entities;
    }

    /**
     * @var int $iteratorIndex
     */
    private $iteratorIndex = 0;

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
     * @return void
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
}
