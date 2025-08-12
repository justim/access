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

namespace Tests\Base;

use Access\Exception;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

/**
 * SQLite has no support for locks, the tests are just "covering" the lock code
 *
 * Add some tests when we run our tests on a different database
 */
abstract class BaseLockTest extends TestCase implements DatabaseBuilderInterface
{
    public function testLockRead(): void
    {
        $db = static::createDatabaseWithDummyData();

        $lock = $db->createLock();
        $lock->read(Project::class);
        $lock->read(User::class, 'u');
        $lock->lock();
        $lock->unlock();

        $this->assertTrue(true);
    }

    public function testLockWrite(): void
    {
        $db = static::createDatabaseWithDummyData();

        $lock = $db->createLock();
        $lock->write(Project::class);
        $lock->write(User::class, 'u');
        $lock->lock();
        $lock->unlock();

        $this->assertTrue(true);
    }

    public function testDestructor(): void
    {
        $db = static::createDatabaseWithDummyData();

        $lock = $db->createLock();
        $lock->write(Project::class);
        $lock->write(User::class, 'u');
        $lock->lock();

        $this->expectException(Exception::class);

        // tables are never unlocked, this still works in
        // SQLite without actually locking the tables
        unset($lock);
    }

    public function testEmptyLock(): void
    {
        $db = static::createDatabaseWithDummyData();

        $lock = $db->createLock();
        $lock->lock();

        // No tables are locked, destructor should not throw
        unset($lock);

        $this->assertTrue(true);
    }

    public function testEmptyUnlock(): void
    {
        $db = static::createDatabaseWithDummyData();

        $lock = $db->createLock();
        $lock->lock();
        $lock->unlock();

        // nothing happens

        $this->assertTrue(true);
    }

    public function testLockContains(): void
    {
        $db = static::createDatabaseWithDummyData();

        $lock1 = $db->createLock();
        $lock1->read(User::class, 'u');
        $lock1->write(Project::class);

        $this->assertTrue($lock1->contains($lock1));
        $this->assertTrue($lock1->contains($db->createLock()));

        $lock2 = $db->createLock();
        $lock2->read(User::class, 'u');

        $this->assertTrue($lock1->contains($lock2));
        $this->assertFalse($lock2->contains($lock1));
    }

    public function testLockContainerAfterMerge(): void
    {
        $db = static::createDatabaseWithDummyData();

        $lock1 = $db->createLock();
        $lock1->read(User::class, 'u');

        $this->assertTrue($lock1->contains($lock1));

        $lock2 = $db->createLock();
        $lock2->write(Project::class);

        $this->assertFalse($lock1->contains($lock2));
        $this->assertFalse($lock2->contains($lock1));

        $lock1->merge($lock2);

        $this->assertTrue($lock1->contains($lock2));
        $this->assertFalse($lock2->contains($lock1));
    }
}
