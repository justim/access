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

namespace Access\Profiler;

use Access\Query;

/**
 * Profile for a single query
 *
 * @author Tim <me@justim.net>
 */
class QueryProfile implements QueryProfileInterface
{
    private Query $query;

    private float $prepareDurationStart = 0.0;

    private float $prepareDurationEnd = 0.0;

    private float $executeDurationStart = 0.0;

    private float $executeDurationEnd = 0.0;

    private float $hydrateDurationStart = 0.0;

    private float $hydrateDurationEnd = 0.0;

    private ?int $numberOfResults = null;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Get the query for this profile
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    public function startPrepare(): void
    {
        $this->prepareDurationStart = microtime(true);
    }

    public function endPrepare(): void
    {
        $this->prepareDurationEnd = microtime(true);
    }

    /**
     * Get prepare duration in seconds
     */
    public function getPrepareDuration(): float
    {
        return $this->prepareDurationEnd - $this->prepareDurationStart;
    }

    public function startExecute(): void
    {
        $this->executeDurationStart = microtime(true);
    }

    public function endExecute(): void
    {
        $this->executeDurationEnd = microtime(true);
    }

    /**
     * Get execute duration in seconds
     */
    public function getExecuteDuration(): float
    {
        return $this->executeDurationEnd - $this->executeDurationStart;
    }

    public function startHydrate(): void
    {
        $this->hydrateDurationStart = microtime(true);
    }

    public function endHydrate(): void
    {
        $this->hydrateDurationEnd = microtime(true);
    }

    /**
     * Get hydrate duration in seconds
     */
    public function getHydrateDuration(): float
    {
        return $this->hydrateDurationEnd - $this->hydrateDurationStart;
    }

    /**
     * Set number of results
     */
    public function setNumberOfResults(?int $numberOfResults): void
    {
        $this->numberOfResults = $numberOfResults;
    }

    /**
     * Get number of results
     *
     * This might not be accurate if the looping over the results of the query
     * is cut short for any reason, it's the number of records that have been
     * yielded by the statement
     */
    public function getNumberOfResults(): ?int
    {
        return $this->numberOfResults;
    }

    /**
     * Get total duration in seconds
     *
     * - Prepare duration
     * - Execute duration
     *
     * Actual fetching of data is not included, because we yield all records
     * directly to the caller, skewing the time it takes to fetch. You can get
     * the duration of the fetching with `getHydrateDuration` or
     * `getTotalDurationWithHydrate`
     */
    public function getTotalDuration(): float
    {
        return $this->getPrepareDuration() + $this->getExecuteDuration();
    }

    /**
     * Get total duration in seconds
     *
     * - Prepare duration
     * - Execute duration
     * - Hydrate duration
     */
    public function getTotalDurationWithHydrate(): float
    {
        return $this->getPrepareDuration() +
            $this->getExecuteDuration() +
            $this->getHydrateDuration();
    }
}
