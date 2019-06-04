<?php

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
