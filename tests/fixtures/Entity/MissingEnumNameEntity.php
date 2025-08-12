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

use Tests\Fixtures\UserStatus;

/**
 * Missing enum name
 */
class MissingEnumNameEntity extends Entity
{
    public static function tableName(): string
    {
        return 'users';
    }

    public static function fields(): array
    {
        return [
            'status' => [
                'type' => self::FIELD_TYPE_ENUM,
            ],
        ];
    }

    public function setStatus(UserStatus $status): void
    {
        $this->set('status', $status);
    }
}
