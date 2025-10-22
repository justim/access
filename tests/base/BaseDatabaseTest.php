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

use Access\Database;
use Access\Exception;
use Access\Exception\ClosedConnectionException;
use Access\Query;
use PDO;
use PHPUnit\Framework\TestCase;

use Tests\Fixtures\Entity\Photo;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\Role;
use Tests\Fixtures\Entity\User;

abstract class BaseDatabaseTest extends TestCase implements DatabaseBuilderInterface
{
    public function testBrokenCreation(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid database: blabla');

        Database::create('blabla');
    }

    public function testDirectConstruct(): void
    {
        $db = new Database(new PDO('sqlite::memory:'));

        $this->assertNotNull($db);
    }

    public function testInsert(): void
    {
        $db = static::createDatabase();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        $db->insert($dave);

        $this->assertEquals(1, $dave->getId());
        $this->assertNotNull($dave->getCreatedAt());
        $this->assertNotNull($dave->getUpdatedAt());

        $bob = new User();
        $bob->setEmail('bob@example.com');
        $bob->setName('Bob');

        $db->insert($bob);

        $this->assertEquals(2, $bob->getId());
        $this->assertNotNull($bob->getCreatedAt());
        $this->assertNotNull($bob->getUpdatedAt());

        $access = new Project();
        $access->setOwnerId($dave->getId());
        $access->setName('Access');
        $access->setPublishedAt(\DateTime::createFromFormat('Y-m-d', '2019-02-07') ?: null);

        $this->assertFalse($access->hasId());

        $db->save($access);

        $this->assertTrue($access->hasId());
        $this->assertEquals(1, $access->getId());
        $this->assertNotNull($access->getCreatedAt());

        $db->save($access);

        $this->assertTrue($access->hasId());

        $accessFork = new Project();
        $accessFork->setOwnerId($bob->getId());
        $accessFork->setName('Access fork');

        $db->insert($accessFork);

        $this->assertEquals(2, $accessFork->getId());
        $this->assertNotNull($accessFork->getCreatedAt());
    }

    public function testFindOne(): void
    {
        $db = static::createDatabaseWithDummyData();

        $user = $db->findOne(User::class, 1);

        $this->assertNotNull($user);
    }

    public function testFindOneBy(): void
    {
        $db = static::createDatabaseWithDummyData();

        $user = $db->findOneBy(User::class, [
            'name' => 'Dave',
        ]);

        $this->assertNotNull($user);
    }

    public function testFindByNoFields(): void
    {
        $db = static::createDatabaseWithDummyData();

        $users = $db->findBy(User::class, []);
        $count = 0;

        $this->assertIsIterable($users);
        $this->assertTrue($users instanceof \Generator);

        foreach ($users as $id => $user) {
            $count++;

            $this->assertEquals($id, $user->getId());
        }

        $this->assertEquals(2, $count);
    }

    public function testUpdate(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 1);
        $this->assertNotNull($user);

        $this->assertFalse($user->hasChanges());
        $user->setName('Dave 2');
        $this->assertTrue($user->hasChanges());

        $db->update($user);

        $this->assertEquals('Dave 2', $user->getName());
    }

    public function testUpdateSame(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 1);
        $this->assertNotNull($user);

        $user->setName('Dave 2');

        $db->update($user);

        $this->assertEquals('Dave 2', $user->getName());
    }

    public function testDelete(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var Project $project */
        $project = $db->findOne(Project::class, 2);
        $this->assertNotNull($project);

        $db->delete($project);

        /** @var Project|null $project */
        $project = $db->findOne(Project::class, 2);
        $this->assertNull($project);
    }

    public function testDeleteCascade(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 2);
        $this->assertNotNull($user);

        /** @var Project $project */
        $project = $db->findOne(Project::class, 2);
        $this->assertNotNull($project);

        $db->delete($user);

        /** @var User|null $user */
        $user = $db->findOne(User::class, 2);
        $this->assertNull($user);

        /** @var Project|null $project */
        $project = $db->findOne(Project::class, 2);
        $this->assertNull($project);
    }

    public function testSoftDeleteCascade(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 2);
        $this->assertNotNull($user);

        /** @var Project $project */
        $project = $db->findOne(Project::class, 2);
        $this->assertNotNull($project);

        $db->softDelete($user);

        /** @var User|null $user */
        $user = $db->findOne(User::class, 2);
        $this->assertNull($user);

        // still here, project is not soft deletable
        /** @var Project|null $project */
        $project = $db->findOne(Project::class, 2);
        $this->assertNotNull($project);
    }

    public function testQuerySelect(): void
    {
        $db = static::createDatabaseWithDummyData();

        $query = new Query\Select(Project::class);
        $query->where([
            'name = ?' => 'Access',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Method does not allow select queries, use `select` or `selectOne`',
        );

        $db->query($query);
    }

    public function testQueryUpdate(): void
    {
        $db = static::createDatabaseWithDummyData();

        $query = new Query\Update(Project::class);
        $query->values(['name' => 'Access']);
        $query->where(['name = ?' => 'Access']);

        $db->query($query);

        // no exception
        $this->assertEquals(1, 1);
    }

    public function testKlassValidationInvalid(): void
    {
        $db = static::createDatabaseWithDummyData();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid entity: BLABLA');

        /**
         * SAFEFY we validate the `findOne` method here
         * @psalm-suppress UndefinedClass
         * @psalm-suppress ArgumentTypeCoercion
         */
        $db->findOne('BLABLA', 1);
    }

    public function testKlassValidationEmpty(): void
    {
        $db = static::createDatabaseWithDummyData();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid table name, can not be empty');

        $db->findOne(Role::class, 1);
    }

    public function testRepositoryValidation(): void
    {
        $db = static::createDatabaseWithDummyData();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid repository: BLABLA');

        $db->getRepository(Photo::class);
    }

    public function testNonSoftDeletable(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var Project $project */
        $project = $db->findOne(Project::class, 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Entity is not soft deletable');

        $db->softDelete($project);
    }

    public function testWithIncludeSoftDeleted(): void
    {
        $db = static::createDatabaseWithDummyData();

        // user exists in the database
        $user = $db->findOne(User::class, 1);
        $this->assertNotNull($user);

        $db->softDelete($user);

        // user is soft deleted
        $user = $db->findOne(User::class, 1);
        $this->assertNull($user);

        // create a new database instance with include soft deleted
        $db = $db->withIncludeSoftDeleted(true);

        // the user is findable again
        $user = $db->findOne(User::class, 1);
        $this->assertNotNull($user);
    }

    public function testCloseConnection(): void
    {
        $db = static::createDatabaseWithDummyData();

        $connection = $db->getConnection();
        self::assertInstanceOf(PDO::class, $connection);

        $db->closeConnection();

        self::expectException(ClosedConnectionException::class);
        self::expectExceptionMessage('Connection is closed');
        $db->getConnection();
    }
}
