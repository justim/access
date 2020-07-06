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

namespace Tests;

use Access\Database;
use Access\Exception;
use Access\Query;

use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\Photo;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\Role;
use Tests\Fixtures\Entity\User;

class DatabaseTest extends AbstractBaseTestCase
{
    public function testBrokenCreation(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid database: blabla');

        Database::create('blabla');
    }

    public function testDirectConstruct(): void
    {
        $db = new Database(new \PDO('sqlite::memory:'));

        $this->assertNotNull($db);
    }

    /**
     * @depends testInsert
     */
    public function testFindOne(): void
    {
        $user = self::$db->findOne(User::class, 1);

        $this->assertNotNull($user);
    }

    /**
     * @depends testInsert
     */
    public function testFindOneBy(): void
    {
        $user = self::$db->findOneBy(User::class, [
            'name' => 'Dave',
        ]);

        $this->assertNotNull($user);
    }

    /**
     * @depends testInsert
     */
    public function testFindBy(): void
    {
        $users = self::$db->findBy(User::class, []);
        $count = 0;

        $this->assertIsIterable($users);
        $this->assertTrue($users instanceof \Generator);

        foreach ($users as $id => $user) {
            $count++;

            $this->assertEquals($id, $user->getId());
        }

        $this->assertEquals(2, $count);
    }

    /**
     * @depends testInsert
     */
    public function testUpdate(): void
    {
        /** @var User $user */
        $user = self::$db->findOne(User::class, 1);
        $this->assertNotNull($user);

        $this->assertFalse($user->hasChanges());
        $user->setName('Dave 2');
        $this->assertTrue($user->hasChanges());

        self::$db->update($user);

        $this->assertEquals('Dave 2', $user->getName());
    }

    /**
     * @depends testInsert
     */
    public function testUpdateSame(): void
    {
        /** @var User $user */
        $user = self::$db->findOne(User::class, 1);
        $this->assertNotNull($user);

        $user->setName('Dave 2');

        self::$db->update($user);

        $this->assertEquals('Dave 2', $user->getName());
    }

    /**
     * @depends testInsert
     */
    public function testDelete(): void
    {
        /** @var User $user */
        $user = self::$db->findOne(User::class, 2);
        $this->assertNotNull($user);

        self::$db->delete($user);

        /** @var User|null $user */
        $user = self::$db->findOne(User::class, 2);
        $this->assertNull($user);
    }

    /**
     * @depends testInsert
     */
    public function testQuerySelect()
    {
        $query = new Query\Select(Project::class);
        $query->where([
            'name = ?' => 'Access',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Method does not allow select queries, use `select` or `selectOne`',
        );

        self::$db->query($query);
    }

    /**
     * @depends testInsert
     */
    public function testQueryUpdate()
    {
        $query = new Query\Update(Project::class);
        $query->values(['name' => 'Access']);
        $query->where(['name = ?' => 'Access']);

        self::$db->query($query);

        // no exception
        $this->assertEquals(1, 1);
    }

    public function testKlassValidationInvalid()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid entity: BLABLA');

        self::$db->findOne('BLABLA', 1);
    }

    public function testKlassValidationEmpty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid table name, can not be empty');

        self::$db->findOne(Role::class, 1);
    }

    public function testRepositoryValidation()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid repository: BLABLA');

        self::$db->getRepository(Photo::class);
    }
}
