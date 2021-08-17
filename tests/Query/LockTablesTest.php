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

namespace Tests\Query;

use Access\Query;
use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

/**
 * SQLite has no support for locks
 *
 * Add some tests when we run our tests on a different database
 */
class LockTablesTest extends AbstractBaseTestCase
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
}
