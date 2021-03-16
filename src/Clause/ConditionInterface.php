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

namespace Access\Clause;

use Access\Entity;

/**
 * Clause is a condition
 *
 * @author Tim <me@justim.net>
 */
interface ConditionInterface extends ClauseInterface
{
    /**
     * Test given entity against condition
     *
     * @param Entity|null $entity Entity to compare
     * @return bool Does the condition match the entity
     */
    public function matchesEntity(?Entity $entity): bool;
}
