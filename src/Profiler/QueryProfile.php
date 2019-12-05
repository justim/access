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
class QueryProfile
{
    /**
     * @var Query $query
     */
    private $query;

    /**
     * @var float
     */
    private $prepareDurationStart = 0.0;

    /**
     * @var float
     */
    private $prepareDurationEnd = 0.0;

    /**
     * @var float
     */
    private $executeDurationStart = 0.0;

    /**
     * @var float
     */
    private $executeDurationEnd = 0.0;

    /**
     * @var float
     */
    private $hydrateDurationStart = 0.0;

    /**
        * @var float
     */
    private $hydrateDurationEnd = 0.0;

    /**
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Get the query for this profile
     *
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * Start of prepare
     */
    public function startPrepare(): void
    {
        $this->prepareDurationStart = microtime(true);
    }

    /**
     * End of prepare
     */
    public function endPrepare(): void
    {
        $this->prepareDurationEnd = microtime(true);
    }

    /**
     * Get prepare duration in seconds
     *
     * @return float
     */
    public function getPrepareDuration(): float
    {
        return $this->prepareDurationEnd - $this->prepareDurationStart;
    }

    /**
     * Start of execute
     */
    public function startExecute(): void
    {
        $this->executeDurationStart = microtime(true);
    }

    /**
     * End of execute
     */
    public function endExecute(): void
    {
        $this->executeDurationEnd = microtime(true);
    }

    /**
     * Get execute duration in seconds
     *
     * @return float
     */
    public function getExecuteDuration(): float
    {
        return $this->executeDurationEnd - $this->executeDurationStart;
    }

    /**
     * Start of hydrate
     */
    public function startHydrate(): void
    {
        $this->hydrateDurationStart = microtime(true);
    }

    /**
     * End of hydrate
     */
    public function endHydrate(): void
    {
        $this->hydrateDurationEnd = microtime(true);
    }

    /**
     * Get hydrate duration in seconds
     *
     * @return float
     */
    public function getHydrateDuration(): float
    {
        return $this->hydrateDurationEnd - $this->hydrateDurationStart;
    }

    /**
     * Get total duration in seconds
     *
     * - Prepare duration
     * - Execute duration
     *
     * Acutal fetching of data is not included, because we yield all records
     * directly to the caller, skewing the time it takes to fetch. You can get
     * the duration of the fetching with `getHydrateDuration` or
     * `getTotalDurationWithHydrate`
     *
     * @return float
     */
    public function getTotalDuration(): float
    {
        return $this->getPrepareDuration()
            + $this->getExecuteDuration();
    }

    /**
     * Get total duration in seconds
     *
     * - Prepare duration
     * - Execute duration
     * - Hydrate duration
     *
     * @return float
     */
    public function getTotalDurationWithHydrate(): float
    {
        return $this->getPrepareDuration()
            + $this->getExecuteDuration()
            + $this->getHydrateDuration();
    }
}
