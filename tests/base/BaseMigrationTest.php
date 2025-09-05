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
use Access\Migrations\MigrationEntity;
use Access\Migrations\Migrator;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\Migrations\Version2025080412000;
use Tests\Fixtures\Migrations\Version2025080412001;

abstract class BaseMigrationTest extends TestCase implements DatabaseBuilderInterface
{
    public function testMigration(): void
    {
        $db = static::createEmptyDatabase();

        $migrator = new Migrator($db, MigrationEntity::class);
        $migrator->init();
        $migrator->init(); // second call is noop

        $migration = new Version2025080412000();

        $result = $migrator->constructive($migration);
        $this->assertTrue($result->isSuccess());

        $users = $db->findAll(User::class);

        foreach ($users as $user) {
            $this->assertInstanceOf(User::class, $user);
        }

        // generator is consumed, thus, table is created
        $this->assertNull($users->getReturn());

        $result = $migrator->constructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->revertConstructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->revertConstructive($migration);
        $this->assertFalse($result->isSuccess());

        // table does not exist anymore
        $this->expectException(Exception::class);

        $users = $db->findAll(User::class);

        // consume generator to trigger the query
        iterator_to_array($users);
    }

    public function testMigrationStateMachine(): void
    {
        $db = static::createEmptyDatabase();

        $migrator = new Migrator($db, MigrationEntity::class);
        $migrator->init();

        $migration = new Version2025080412000();

        $result = $migrator->revertConstructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->destructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->revertDestructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->constructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->constructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->revertDestructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->revertConstructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->constructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->destructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->destructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->revertConstructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->revertDestructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->revertDestructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->revertConstructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->destructive($migration);
        $this->assertFalse($result->isSuccess());

        $result = $migrator->constructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->destructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->revertDestructive($migration);
        $this->assertTrue($result->isSuccess());

        $result = $migrator->revertConstructive($migration);
        $this->assertTrue($result->isSuccess());

        $this->expectException(Exception::class);
        $users = $db->findAll(User::class);

        // consume generator to trigger the query
        iterator_to_array($users);
    }

    public function testMigrationWithInsert(): void
    {
        $db = static::createEmptyDatabase();

        $migrator = new Migrator($db);
        $migrator->init();

        $migration = new Version2025080412001();

        $result = $migrator->constructive($migration);
        $this->assertTrue($result->isSuccess());

        $users = $db->findAll(User::class);
        iterator_to_array($users); // consume once
        // generator is consumed, thus, table is created
        $this->assertNull($users->getReturn());
    }
}
