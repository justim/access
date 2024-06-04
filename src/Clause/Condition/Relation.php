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
 * Positive relation between two fields
 *
 * @author Tim <me@justim.net>
 */
class Relation extends Condition
{
    /**
     * Create raw condition
     *
     * @param string|Field $fieldNameOne Field name to compare
     * @param string|Field $fieldNameTwo Field name to compare with
     */
    public function __construct(string|Field $fieldNameOne, string|Field $fieldNameTwo)
    {
        parent::__construct($fieldNameOne, self::KIND_RELATION, $fieldNameTwo);
    }
}
