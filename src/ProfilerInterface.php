<?php

declare(strict_types=1);

namespace Access;

use Access\Profiler\QueryProfile;
use Countable;

interface ProfilerInterface extends Countable
{
    public function createForQuery(Query $query): QueryProfile;
    public function clear(): void;
    public function getTotalDuration(): float;
    public function getTotalDurationWithHydrate(): float;
    public function count(): int;
    public function export(): array;
}
