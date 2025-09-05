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
use Access\Entity\TimestampableTrait;
use Access\Schema\Table;

/**
 * SAFETY Return types are not known, they are stored in an array config
 * @psalm-suppress MixedReturnStatement
 * @psalm-suppress MixedInferredReturnType
 */
class ProfileImage extends Entity
{
    use TimestampableTrait;

    public static function tableName(): string
    {
        return 'profile_images';
    }

    public static function fields(): array
    {
        return [];
    }

    public static function getParentTableSchema(): Table
    {
        return parent::getTableSchema();
    }

    public static function getTableSchema(): Table
    {
        $table = new Table('profile_images', hasCreatedAt: true, hasUpdatedAt: true);

        return $table;
    }
}
