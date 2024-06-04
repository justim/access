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
 * Field must _not_ be null
 *
 * @author Tim <me@justim.net>
 */
class IsNotNull extends Condition
{
    /**
     * Create not null condition
     *
     * @param string|Field $fieldName Name of the field to compare
     */
    public function __construct(string|Field $fieldName)
    {
        parent::__construct($fieldName, self::KIND_NOT_EQUALS, null);
    }
}
