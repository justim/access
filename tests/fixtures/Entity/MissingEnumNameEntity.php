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

    public static function getTableSchema(): Table
    {
        $table = new Table('users');

        /**
         * We be testin'
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress UndefinedClass
         */
        $status = new Field('status', new Type\Enum(''));
        $table->addField($status);

        return $table;
    }

    public function setStatus(UserStatus $status): void
    {
        $this->set('status', $status);
    }
}
