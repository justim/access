<?php

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
