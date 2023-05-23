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

use Access\Entity;

/**
 * Soft deletable functionality for entity
 *
 * A `deleted_at` field must be present
 *
 * @author Tim <me@justim.net>
 */
trait SoftDeletableTrait
{
    /**
     * Is the entity soft deletable
     *
     * A `deleted_at` field must be present
     *
     * @return bool
     */
    public static function isSoftDeletable(): bool
    {
        return true;
    }

    /**
     * Set deleted_at for entity
     */
    public function setDeletedAt(\DateTimeImmutable $now = null): void
    {
        $this->set(Entity::DELETED_AT_FIELD, $now ?? new \DateTimeImmutable());
    }

    /**
     * Get deleted_at for entity
     */
    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->get(Entity::DELETED_AT_FIELD);
    }
}
