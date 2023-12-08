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

namespace Tests\Fixtures\Entity;

use Access\Entity;

/**
 * Missing public soft delete
 */
class MissingSetDeletedEntity extends Entity
{
    public static function tableName(): string
    {
        return 'missing_set_deleted';
    }

    public static function isSoftDeletable(): bool
    {
        return true;
    }

    public static function fields(): array
    {
        return [];
    }
}
