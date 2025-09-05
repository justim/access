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

namespace Access\Entity;

use Access\Schema\Table;

/**
 * Helper methods to work with the create_at field
 *
 * @author Tim <me@justim.net>
 */
trait CreatableTrait
{
    /**
     * Does the entity have `created_at` field
     *
     * @return bool
     */
    public static function creatable(): bool
    {
        return true;
    }

    /**
     * Get the time this entity was created
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->get(Table::CREATED_AT_FIELD);
    }
}
