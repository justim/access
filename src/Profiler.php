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

namespace Access;

use Access\Profiler\QueryProfile;

/**
 * A simple collection of query profiles to keep some timings
 *
 * @author Tim <me@justim.net>
 */
class Profiler implements ProfilerInterface
{
    /**
     * @var QueryProfile[] $queryProfiles
     */
    private array $queryProfiles = [];

    /**
     * Create a query profile for query
     */
    public function createForQuery(Query $query): QueryProfile
    {
        // make sure to create a clone of the query, it might be modified afterwards
        $queryProfile = new QueryProfile(clone $query);
        $this->queryProfiles[] = $queryProfile;

        return $queryProfile;
    }

    /**
     * Clear all profiles from profiler
     */
    public function clear(): void
    {
        $this->queryProfiles = [];
    }

    /**
     * Get the total duration in seconds
     */
    public function getTotalDuration(): float
    {
        $duration = 0.0;

        foreach ($this->queryProfiles as $queryProfile) {
            $duration += $queryProfile->getTotalDuration();
        }

        return $duration;
    }

    /**
     * Get the total duration with hydrate in seconds
     */
    public function getTotalDurationWithHydrate(): float
    {
        $duration = 0.0;

        foreach ($this->queryProfiles as $queryProfile) {
            $duration += $queryProfile->getTotalDurationWithHydrate();
        }

        return $duration;
    }

    /**
     * Return number of query profiles
     */
    public function count(): int
    {
        return count($this->queryProfiles);
    }

    /**
     * Get a flat export of query profiles
     * 
     * @psalm-return array{duration: float, durationWithHydrate: float, queries: array<array-key, array{sql: ?string, values: mixed[], runnableSql: ?string, duration: float, durationWithHydrate: float, numberOfResults: ?int}>}
     */
    public function export(): array
    {
        return [
            'duration' => $this->getTotalDuration(),
            'durationWithHydrate' => $this->getTotalDurationWithHydrate(),
            'queries' => array_map(function (QueryProfile $queryProfile) {
                $query = $queryProfile->getQuery();
                $debugQuery = new DebugQuery($query);

                return [
                    'sql' => $query->getSql(),
                    'values' => $query->getValues(),
                    'runnableSql' => $debugQuery->toRunnableQuery(),
                    'duration' => $queryProfile->getTotalDuration(),
                    'durationWithHydrate' => $queryProfile->getTotalDurationWithHydrate(),
                    'numberOfResults' => $queryProfile->getNumberOfResults(),
                ];
            }, $this->queryProfiles),
        ];
    }
}
