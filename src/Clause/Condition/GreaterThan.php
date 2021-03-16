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

namespace Access\Clause\Condition;

/**
 * Field must be equal to value
 *
 * @author Tim <me@justim.net>
 */
class GreaterThan extends Condition
{
    /**
     * Create equal condition
     *
     * @param string $fieldName Name of the field to compare
     * @param mixed $value Value to compare
     */
    public function __construct(string $fieldName, $value)
    {
        parent::__construct($fieldName, self::KIND_GREATER_THAN, $value);
    }
}
