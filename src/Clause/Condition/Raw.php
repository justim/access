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
 * Raw condition
 *
 * The condition is used directly in the SQL query
 *
 * @internal
 *
 * @author Tim <me@justim.net>
 */
class Raw extends Condition
{
    /**
     * Create raw condition
     *
     * @param string $condition Raw condition
     * @param mixed $value Value for the raw condition
     */
    public function __construct(string $condition, mixed $value = null)
    {
        parent::__construct($condition, self::KIND_RAW, $value);
    }

    /**
     * Return the condition as string
     */
    public function getCondition(): string
    {
        return $this->getField()->getName();
    }
}
