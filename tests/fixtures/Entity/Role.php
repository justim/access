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
 * Invalid table name
 */
class Role extends Entity
{
    public static function tableName(): string
    {
        return '';
    }

    public static function fields(): array
    {
        return [];
    }
}
