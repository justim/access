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

use Access\Clause\Field;

/**
 * Field must be equal to value
 *
 * @author Tim <me@justim.net>
 */
class GreaterThanOrEquals extends Condition
{
    /**
     * Create equal condition
     *
     * @param string|Field $fieldName Name of the field to compare
     * @param mixed $value Value to compare
     */
    public function __construct(string|Field $fieldName, mixed $value)
    {
        parent::__construct($fieldName, self::KIND_GREATER_THAN_OR_EQUALS, $value);
    }
}
