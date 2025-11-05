<?php

declare(strict_types=1);

namespace Access\Profiler;

use Access\Query;

interface QueryProfileInterface
{
    public function __construct(Query $query);
    public function getQuery(): Query;
    public function startPrepare(): void;
    public function endPrepare(): void;
    public function getPrepareDuration(): float;
    public function startExecute(): void;
    public function endExecute(): void;
    public function getExecuteDuration(): float;
    public function startHydrate(): void;
    public function endHydrate(): void;
    public function getHydrateDuration(): float;
    public function setNumberOfResults(?int $numberOfResults): void;
    public function getNumberOfResults(): ?int;
    public function getTotalDuration(): float;
    public function getTotalDurationWithHydrate(): float;
}
