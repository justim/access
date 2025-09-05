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
use Access\Schema\Table;

/**
 * Invalid repository
 */
class Photo extends Entity
{
    /**
     * SAFEFY this is on purpose for testing
     * @psalm-suppress MoreSpecificReturnType
     */
    public static function getRepository(): string
    {
        /**
         * SAFEFY this is on purpose for testing
         * @psalm-suppress UndefinedClass
         * @psalm-suppress LessSpecificReturnStatement
         * @psalm-suppress MoreSpecificReturnType
         */
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

    public static function getTableSchema(): Table
    {
        $table = new Table('photos', hasCreatedAt: true, hasUpdatedAt: true);

        return $table;
    }
}
