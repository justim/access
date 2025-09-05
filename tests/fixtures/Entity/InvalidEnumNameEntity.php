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
use Access\Schema\Field;
use Access\Schema\Table;
use Access\Schema\Type;
use Tests\Fixtures\UserStatus;

/**
 * Invalid enum name
 */
class InvalidEnumNameEntity extends Entity
{
    public static function tableName(): string
    {
        return 'users';
    }

    /**
     * We testing here, just let us be...
     * @psalm-suppress InvalidReturnType
     */
    public static function fields(): array
    {
        /** @psalm-suppress InvalidReturnStatement */
        return [
            'status' => [
                'type' => self::FIELD_TYPE_ENUM,
                'enumName' => InvalidEnumNameEntity::class,
            ],
        ];
    }

    public static function getTableSchema(): Table
    {
        $table = new Table('users');

        /** @psalm-suppress InvalidArgument */
        $status = new Field('status', new Type\Enum(InvalidEnumNameEntity::class));
        $table->addField($status);

        return $table;
    }

    public function setStatus(UserStatus $status): void
    {
        $this->set('status', $status);
    }
}
