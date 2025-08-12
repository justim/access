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

namespace Tests\Sqlite;

use Access\Exception;
use Access\Query;
use PHPUnit\Framework\TestCase;

use Tests\Fixtures\Entity\User;

class StatementTest extends TestCase
{
    use DatabaseBuilderTrait;

    public function testInvalidPrepare(): void
    {
        $db = static::createDatabaseWithDummyData();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to prepare query');

        $query = new Query\Raw('SELECT foo FRM bar');
        $db->query($query);
    }

    public function testInvalidExectute(): void
    {
        $db = static::createDatabase();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to execute query');

        $query = new Query\Insert(User::class);
        $query->values([
            'id' => 1,
            'name' => 'Dave',
            'email' => 'dave@example.com',
        ]);

        $db->query($query);

        // insert with same primary key value
        $db->query($query);
    }
}
