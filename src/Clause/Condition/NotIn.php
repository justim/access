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
 * Field must not be in the list of values
 *
 * @author Tim <me@justim.net>
 */
class NotIn extends Condition
{
    /**
     * Create not-in condition
     *
     * @param string|Field $fieldName Name of the field to compare
     * @param iterable<mixed> $values List of values
     */
    public function __construct(string|Field $fieldName, iterable $values)
    {
        parent::__construct($fieldName, self::KIND_NOT_IN, $values);
    }
}
