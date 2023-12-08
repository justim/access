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

namespace Access;

use Access\Cascade\CascadeDeleteKind;

/**
 * Cascade options for entity relations
 *
 * @author Tim <me@justim.net>
 */
class Cascade
{
    private CascadeDeleteKind $delete;

    /**
     * Only way to create a new instance is via static methods
     */
    private function __construct(CascadeDeleteKind $delete)
    {
        $this->delete = $delete;
    }

    /**
     * Cascade the delete in same way this entity is deleted
     *
     * Soft deletes will cascade soft deletes, regular deletes will cascade
     * regular deletes. If a entity is not soft deletable, the soft delete will
     * be ignored.
     *
     * @return self
     */
    public static function deleteSame(): self
    {
        return new self(CascadeDeleteKind::Same);
    }

    /**
     * Force cascade a regular delete
     *
     * Cascade a regular delete, even if the entity is soft deletable. If this
     * entity is soft deleted, the related entity will be regularly deleted.
     *
     * @return self
     */
    public static function deleteForceRegular(): self
    {
        return new self(CascadeDeleteKind::ForceRegular);
    }

    /**
     * @param DeleteKind $kind
     * @param string $entityName
     * @psalm-param class-string<Entity> $entityName
     */
    public function shouldCascadeDelete(DeleteKind $kind, string $entityName): bool
    {
        return $this->shouldCascadeDeleteRegular($kind) ||
            $this->shouldCascadeDeleteSoft($kind, $entityName);
    }

    /**
     * @param DeleteKind $kind
     */
    public function shouldCascadeDeleteRegular(DeleteKind $kind): bool
    {
        return $this->delete === CascadeDeleteKind::ForceRegular ||
            ($this->delete === CascadeDeleteKind::Same && $kind === DeleteKind::Regular);
    }

    /**
     * Is the entity allowed to be soft deleted
     *
     * @param DeleteKind $kind
     * @param string $entityName
     * @psalm-param class-string<Entity> $entityName
     */
    public function shouldCascadeDeleteSoft(DeleteKind $kind, string $entityName): bool
    {
        return $this->delete === CascadeDeleteKind::Same &&
            $kind === DeleteKind::Soft &&
            $entityName::isSoftDeletable();
    }
}
