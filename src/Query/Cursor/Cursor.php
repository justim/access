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

use Access\Query;

/**
 * Query cursor information
 *
 * @author Tim <me@justim.net>
 */
abstract class Cursor
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
    public function __construct(int $pageSize = self::DEFAULT_PAGE_SIZE)
    {
        $this->setPageSize($pageSize);
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
}
