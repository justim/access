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

namespace Access\Clause\OrderBy;

use Access\Query\QueryGeneratorState;

/**
 * Random sort order
 *
 * @author Tim <me@justim.net>
 */
class Random extends OrderBy
{
    /**
     * Create a random sort clause
     */
    public function __construct()
    {
        // override parent constructor, parent is protected and expects some arguments
    }

    /**
     * {@inheritdoc}
     */
    public function createSortComparer(): callable
    {
        // a function that returns a random result of `a <=> b`
        return fn(): int => random_int(-1, 1);
    }

    public function getConditionSql(QueryGeneratorState $state): string
    {
        return 'RANDOM()';
    }
}
