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

namespace Access\Query\Cursor;

use Access\Clause\Filter\FilterItemResult;
use Access\Clause\FilterInterface;
use Access\Collection;
use Access\Exception\NotSupportedException;
use Access\Query;

/**
 * Query cursor information
 *
 * @author Tim <me@justim.net>
 */
abstract class Cursor implements FilterInterface
{
    /**
     * Default page size
     *
     * @var int
     */
    public const DEFAULT_PAGE_SIZE = 50;

    /**
     * Size of the pages
     *
     * @var int $pageSize
     */
    protected int $pageSize;

    /**
     * Create a pagination cursor
     *
     * @param int $pageSize Page size, defaults to 50
     */
    public function __construct(?int $pageSize = self::DEFAULT_PAGE_SIZE)
    {
        $this->setPageSize($pageSize ?? self::DEFAULT_PAGE_SIZE);
    }

    /**
     * Get the page size of the cursor
     *
     * @return int Page size
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Set the page size of the cursor
     *
     * @param int $pageSize Page size, defaults to 50
     */
    public function setPageSize(int $pageSize = self::DEFAULT_PAGE_SIZE): void
    {
        $this->pageSize = $pageSize;
    }

    /**
     * Apply the this cursor to the query
     *
     * @param Query $query The query that needs cursoring
     */
    abstract public function apply(Query $query): void;

    /**
     * Filter given collection in place based on this cursor
     *
     * @psalm-template TEntity of \Access\Entity
     * @param Collection $collection The collection that needs cursoring
     * @psalm-param Collection<TEntity> $collection The collection that needs cursoring
     * @return Collection The filtered collection
     * @psalm-return Collection<TEntity> The filtered collection
     */
    public function filterCollection(Collection $collection): Collection
    {
        return $collection->filter($this->createFilterFinder());
    }

    /**
     * Create the finder function for this cursor
     *
     * @return callable
     * @psalm-return callable(\Access\Entity): (FilterItemResult|bool)
     */
    public function createFilterFinder(): callable
    {
        throw new NotSupportedException(sprintf(
            'The "%s" cursor does not support collections',
            get_class($this),
        ));
    }
}
