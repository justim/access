<?php

declare(strict_types=1);

namespace Access;

use Access\Profiler\QueryProfile;
use Access\Query;

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
     * Get a flat export of query profiles
     *
     * @return array
     */
    public function export(): array
    {
        return [
            'duration' => $this->getTotalDuration(),
            'queries' => array_map(
                function (QueryProfile $queryProfile) {
                    return [
                        'sql' => $queryProfile->getQuery()->getSql(),
                        'values' => $queryProfile->getQuery()->getValues(),
                        'duration' => $queryProfile->getTotalDuration(),
                    ];
                },
                $this->queryProfiles
            ),
        ];
    }
}
