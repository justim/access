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

/**
 * Descending sort clause
 *
 * @author Tim <me@justim.net>
 */
class Descending extends OrderBy
{
    /**
     * Create a descending sort clause
     *
     * @param string $fieldName Field to sort on
     */
    public function __construct(string $fieldName)
    {
        parent::__construct($fieldName, Direction::Descending);
    }
}
