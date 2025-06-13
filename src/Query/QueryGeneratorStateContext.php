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

namespace Access\Query;

/**
 * Query generator state context
 *
 * @internal
 *
 * @author Tim <me@justim.net>
 */
enum QueryGeneratorStateContext
{
    case Condition;
    case OrderBy;

    public function allowEmptyMultiple(): bool
    {
        return match ($this) {
            self::Condition => false,
            self::OrderBy => true,
        };
    }
}
