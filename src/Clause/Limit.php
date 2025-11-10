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

namespace Access\Clause;

use Access\Collection;
use Access\Query\QueryGeneratorState;

/**
 * Limit the number of entities
 *
 * @author Tim <me@justim.net>
 */
class Limit implements LimitInterface
{
    /**
     * The limit of number of entities
     */
    private int $limit;

    /**
     * Starting offset
     */
    private ?int $offset = null;

    public function __construct(int $limit, ?int $offset = null)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * Get the current limit
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Set a new limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Get the current offset
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * Set a new offset
     */
    public function setOffset(?int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * {@inheritdoc}
     */
    public function limitCollection(?Collection $collection): void
    {
        if ($collection === null) {
            return;
        }

        $collection->limit($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionSql(QueryGeneratorState $state): string
    {
        $limitSql = " LIMIT {$this->limit}";

        if ($this->offset !== null) {
            $limitSql .= " OFFSET {$this->offset}";
        }

        return $limitSql;
    }

    /**
     * {@inheritdoc}
     */
    public function injectConditionValues(QueryGeneratorState $state): void
    {
        // no values to inject
    }
}
