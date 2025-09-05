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
 * Helper methods to work with the timestamps
 *
 * @author Tim <me@justim.net>
 */
trait TimestampableTrait
{
    use CreatableTrait;

    /**
     * Does the entity have timestamps
     *
     * A `created_at` and `updated_at` date time field
     *
     * @return bool
     */
    public static function timestamps(): bool
    {
        return true;
    }

    /**
     * Get the time this entity was last updated
     *
     * @return \DateTimeImmutable
     */
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->get(Table::UPDATED_AT_FIELD);
    }
}
