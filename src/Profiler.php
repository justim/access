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
use Access\Query;

/**
 * A simple collection of query profiles to keep some timings
 *
 * @author Tim <me@justim.net>
 */
class Profiler
{
    /**
     * @var QueryProfile[] $queryProfiles
     */
    private $queryProfiles = [];

    /**
     * Create a query profile for query
     *
     * @param Query $query
     * @return QueryProfile
     */
    public function createForQuery(Query $query): QueryProfile
    {
        $queryProfile = new QueryProfile($query);
        $this->queryProfiles[] = $queryProfile;

        return $queryProfile;
    }

    /**
     * Get the total duration in seconds
     *
     * @return float
     */
    public function getTotalDuration(): float
    {
        $duration = 0;

        foreach ($this->queryProfiles as $queryProfile) {
            $duration += $queryProfile->getTotalDuration();
        }

        return $duration;
    }

    /**
     * Get the total duration with hydrate in seconds
     *
     * @return float
     */
    public function getTotalDurationWithHydrate(): float
    {
        $duration = 0;

        foreach ($this->queryProfiles as $queryProfile) {
            $duration += $queryProfile->getTotalDurationWithHydrate();
        }

        return $duration;
    }

    /**
     * Get a flat export of query profiles
     *
     * @return array
     */
    public function export(): array
    {
        return [
            'duration' => $this->getTotalDuration(),
            'durationWithHydrate' => $this->getTotalDurationWithHydrate(),
            'queries' => array_map(
                function (QueryProfile $queryProfile) {
                    return [
                        'sql' => $queryProfile->getQuery()->getSql(),
                        'values' => $queryProfile->getQuery()->getValues(),
                        'duration' => $queryProfile->getTotalDuration(),
                        'durationWithHydrate' => $queryProfile->getTotalDurationWithHydrate(),
                    ];
                },
                $this->queryProfiles
            ),
        ];
    }
}
