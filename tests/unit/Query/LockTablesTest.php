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

namespace Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use Access\Query;

use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

/**
 * SQLite has no support for locks
 *
 * Add some tests when we run our tests on a different database
 */
class LockTablesTest extends TestCase
{
    public function testLockTablesQueryRead(): void
    {
        $query = new Query\LockTables();
        $query->read(User::class);

        $this->assertEquals('LOCK TABLES `users` READ', $query->getSql());

        $query = new Query\LockTables();
        $query->read(User::class, 'u');

        $this->assertEquals('LOCK TABLES `users` AS `u` READ', $query->getSql());

        $query = new Query\LockTables();
        $query->read(User::class, 'u');
        $query->read(Project::class);

        $this->assertEquals('LOCK TABLES `users` AS `u` READ, `projects` READ', $query->getSql());

        $query = new Query\LockTables();

        $this->assertNull($query->getSql());
    }

    public function testLockTablesQueryWrite(): void
    {
        $query = new Query\LockTables();
        $query->write(User::class);

        $this->assertEquals('LOCK TABLES `users` WRITE', $query->getSql());

        $query = new Query\LockTables();
        $query->write(User::class, 'u');

        $this->assertEquals('LOCK TABLES `users` AS `u` WRITE', $query->getSql());

        $query = new Query\LockTables();
        $query->write(User::class, 'u');
        $query->write(Project::class);

        $this->assertEquals('LOCK TABLES `users` AS `u` WRITE, `projects` WRITE', $query->getSql());
    }

    public function testUnlockTablesQuery(): void
    {
        $query = new Query\UnlockTables();

        $this->assertEquals('UNLOCK TABLES', $query->getSql());
    }

    public function testLockTablesQueryEmpty(): void
    {
        $query = new Query\LockTables();

        $this->assertNull($query->getSql());
    }

    public function testMergeLockTablesQuery(): void
    {
        $query = new Query\LockTables();
        $query->read(User::class, 'u');

        $this->assertEquals('LOCK TABLES `users` AS `u` READ', $query->getSql());

        $query2 = new Query\LockTables();
        $query2->write(Project::class);

        $query->merge($query2);

        $this->assertEquals('LOCK TABLES `users` AS `u` READ, `projects` WRITE', $query->getSql());
    }

    public function testMergeLockWithDuplicatesTablesQuery(): void
    {
        $query = new Query\LockTables();
        $query->read(User::class, 'u');
        $query->write(Project::class);

        $this->assertEquals('LOCK TABLES `users` AS `u` READ, `projects` WRITE', $query->getSql());

        $query2 = new Query\LockTables();
        $query2->read(User::class, 'u');
        $query2->write(Project::class);

        $query->merge($query2);

        $this->assertEquals('LOCK TABLES `users` AS `u` READ, `projects` WRITE', $query->getSql());
    }
}
