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
 * Simple cursor with a page number and page size
 *
 * @author Tim <me@justim.net>
 */
class PageCursor extends Cursor
{
    /**
     * The page number
     *
     * @var int $page
     */
    private int $page = 1;

    /**
     * Create a page cursor
     *
     * @param int $page Page number
     * @param int $pageSize Page size, defaults to 50
     */
    public function __construct(int $page = 1, int $pageSize = self::DEFAULT_PAGE_SIZE)
    {
        parent::__construct($pageSize);

        $this->setPage($page);
    }

    /**
     * Set the page number of cursor
     *
     * @param int $page Page number
     */
    public function setPage(int $page = 1): void
    {
        $this->page = $page;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Query $query): void
    {
        $offset = ($this->page - 1) * $this->pageSize;
        $query->limit($this->pageSize, $offset);
    }
}
