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
 * Invalid repository
 */
class Photo extends Entity
{
    public static function getRepository(): string
    {
        return 'BLABLA';
    }

    public static function tableName(): string
    {
        return 'photos';
    }

    public static function fields(): array
    {
        return [];
    }
}
