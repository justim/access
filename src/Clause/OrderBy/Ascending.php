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

use Access\Clause\Condition\Raw;
use Access\Clause\Field;

/**
 * Ascending sort clause
 *
 * @author Tim <me@justim.net>
 */
class Ascending extends OrderBy
{
    /**
     * Create a ascending sort clause
     *
     * @param string|Field|Raw $fieldName Field to sort on
     */
    public function __construct(string|Field|Raw $fieldName)
    {
        parent::__construct($fieldName, Direction::Ascending);
    }
}
