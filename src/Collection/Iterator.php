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
 * @psalm-template TEntity of Entity
 * @template-implements \Iterator<int, TEntity>
 * @author Tim <me@justim.net>
 */
class Iterator implements \Iterator
{
    /**
     * @var Entity[] $entities
     * @psalm-var list<TEntity> $entities
     */
    private array $entities;

    /**
     * Create a collection iterator
     *
     * @param Entity[] $entities
     * @psalm-param list<TEntity> $entities
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
     * @psalm-return TEntity
     */
    public function current(): Entity
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
    public function key(): int
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
    public function next(): void
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
    public function rewind(): void
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
    public function valid(): bool
    {
        return isset($this->entities[$this->iteratorIndex]);
    }
}
