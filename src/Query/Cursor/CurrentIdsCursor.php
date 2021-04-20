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
 * Simple cursor with a list of current IDs
 *
 * @author Tim <me@justim.net>
 */
class CurrentIdsCursor extends Cursor
{
    /**
     * The current IDs
     *
     * @var int[] $currentIds
     */
    private array $currentIds = [];

    /**
     * Create a current IDs cursor
     *
     * @param int[] $currentIds The current IDs
     * @param int $pageSize Page size, defaults to 50
     */
    public function __construct(array $currentIds = [], int $pageSize = self::DEFAULT_PAGE_SIZE)
    {
        parent::__construct($pageSize);

        $this->setCurrentIds($currentIds);
    }

    /**
     * Set the current IDs for cursor
     *
     * @param int[] $currentIds The current IDs
     */
    public function setCurrentIds(array $currentIds = []): void
    {
        $this->currentIds = $currentIds;
    }

    /**
     * Add IDs to current IDs for cursor
     *
     * @param int[] $currentIds IDs for current IDs
     */
    public function addCurrentIds(array $currentIds = []): void
    {
        $this->currentIds = array_merge($this->currentIds, $currentIds);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Query $query): void
    {
        $query->limit($this->pageSize);

        if (!empty($this->currentIds)) {
            $tableName = $query->getResolvedTableName();

            $query->where(sprintf('%s.id NOT IN (?)', $tableName), $this->currentIds);
        }
    }
}
