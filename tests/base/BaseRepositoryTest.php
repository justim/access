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

use Access\Collection;
use Access\EntityProvider\VirtualArrayEntity;
use Access\Exception;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\Repository\ProjectRepository;
use Tests\Fixtures\Repository\UserRepository;

abstract class BaseRepositoryTest extends TestCase implements DatabaseBuilderInterface
{
    public function testSelectOne(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        /** @var Project $project */
        $project = $projectRepo->findByName('Access');

        $this->assertNotNull($project);
        $this->assertEquals('Access', $project->getName());

        $project = $projectRepo->findByName('BLABLA');

        $this->assertNull($project);
    }

    public function testFindAllWithLimit(): void
    {
        $db = static::createDatabaseWithDummyData();

        $projects = $db->findAll(Project::class, 1);

        foreach ($projects as $project) {
            $this->assertEquals('Access', $project->getName());
        }
    }

    public function testDirectQuery(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        /** @var Project $project */
        $project = $projectRepo->findOne(1);

        $projectRepo->setNameWithDirectQuery($project->getId(), 'Access2');

        /** @var Project $project */
        $project = $projectRepo->findOne(1);
        $this->assertEquals('Access2', $project->getName());
    }

    public function testFindByIds(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projects = $projectRepo->findByIds([1]);

        $this->assertEquals(1, count(iterator_to_array($projects, false)));
    }

    public function testFindByEmptyIds(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projects = $projectRepo->findByIds([]);

        $this->assertEquals(0, count(iterator_to_array($projects, false)));
    }

    public function testFindByIdsAsCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projects = $projectRepo->findByIdsAsCollection([1]);

        $this->assertEquals(1, count(iterator_to_array($projects, false)));
    }

    public function testSelectVirtualField(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $total = $projectRepo->findTotalCount();

        $this->assertEquals(2, $total);
    }

    public function testSelectAddedVirtualField(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $total = $projectRepo->findTotalCountAdded();

        $this->assertEquals(2, $total);
    }

    public function testSelectReplacedVirtualField(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $total = $projectRepo->findTotalCountReplaced();

        $this->assertEquals(2, $total);
    }

    public function testSelectVirtualEntity(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualUserNames();
        $names = iterator_to_array($names, false);

        /**
         * Technically this works, but you probably shouldn't use this in a real scenario.
         * It's a nightmare to get the types right
         *
         * @psalm-suppress MixedInferredReturnType
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress MixedMethodCall
         */
        $names = array_map(fn($name): string => $name->getUserName(), $names);

        $this->assertEquals(['Dave', 'Bob'], $names);
    }

    public function testSelectBrokenVirtualEntity(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Virtual entity provider is missing a `create` implementation',
        );

        $projectRepo->brokenFindVirtualEntity();
    }

    public function testSelectVirtualArrayEntity(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->assertNotNull($names[1]);
        $this->assertInstanceOf(VirtualArrayEntity::class, $names[1]);

        $this->assertEquals('Dave', $names[1]['user_name']);
        $this->assertEquals(1, $names[1]['user_id']);
        $this->assertIsInt($names[1]['user_id']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $names[1]['user_created_at']);
    }

    public function testSelectVirtualEntityIsset(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->assertNotNull($names[1]);

        $this->assertTrue(isset($names[1]['user_name']));
        $this->assertFalse(isset($names[1]['some_other_field']));
    }

    public function testSelectVirtualEntitySingleField(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNamesSingleField();

        /** @var VirtualArrayEntity $name */
        $name = $names->first();

        $this->assertEquals('Dave', $name['user_name']);
    }

    public function testSelectVirtualEntityMissingField(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->assertNotNull($names[1]);
        $this->assertInstanceOf(VirtualArrayEntity::class, $names[1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Field "some_other_field" not available');

        $names[1]['some_other_field'];
    }

    public function testSelectVirtualArrayEntityIllegalSet(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->assertNotNull($names[1]);
        $this->assertInstanceOf(VirtualArrayEntity::class, $names[1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to update virtual array entities');

        $name = $names[1];
        $name['user_name'] = 'Bob';
    }

    public function testSelectVirtualArrayEntityIllegalUnset(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->assertNotNull($names[1]);
        $this->assertInstanceOf(VirtualArrayEntity::class, $names[1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to update virtual array entities');

        unset($names[1]['user_name']);
    }

    public function testSelectdBatched(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $batches = $projectRepo->findBatchedAll();

        $countBatches = 0;
        $countEntities = 0;
        foreach ($batches as $batch) {
            $this->assertEquals(1, count($batch));

            foreach ($batch as $project) {
                $this->assertTrue($project->hasId());

                $countEntities++;
            }

            $countBatches++;
        }

        $this->assertEquals(2, $countBatches);
        $this->assertEquals(2, $countEntities);
    }

    public function testFindBySimple(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projects = $projectRepo->findBy([
            'id' => 1,
        ]);

        $this->assertIsIterable($projects);
        $this->assertTrue($projects instanceof \Generator);
        $this->assertEquals(1, count(iterator_to_array($projects, false)));
    }

    public function testFindByArray(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projects = $projectRepo->findBy([
            'id' => [1],
        ]);

        $this->assertIsIterable($projects);
        $this->assertTrue($projects instanceof \Generator);
        $this->assertEquals(1, count(iterator_to_array($projects, false)));
    }

    public function testFindByRaw(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projects = $projectRepo->findBy([
            'id = ?' => 1,
        ]);

        $this->assertIsIterable($projects);
        $this->assertTrue($projects instanceof \Generator);
        $this->assertEquals(1, count(iterator_to_array($projects, false)));
    }

    public function testFindByEmptyArray(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projects = $projectRepo->findBy([
            'id' => [],
        ]);

        $this->assertIsIterable($projects);
        $this->assertTrue($projects instanceof \Generator);
        $this->assertEquals(0, count(iterator_to_array($projects, false)));
    }

    public function testFindByCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $allProjects = $projectRepo->findAllCollection();

        $projects = $projectRepo->findBy([
            'id' => $allProjects,
        ]);

        $this->assertIsIterable($projects);
        $this->assertTrue($projects instanceof \Generator);
        $this->assertEquals(2, count(iterator_to_array($projects, false)));
    }

    public function testFindByEmptyCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $emptyProjects = new Collection($db);

        $projects = $projectRepo->findBy([
            'id' => $emptyProjects,
        ]);

        $this->assertIsIterable($projects);
        $this->assertTrue($projects instanceof \Generator);
        $this->assertEquals(0, count(iterator_to_array($projects, false)));
    }

    public function testRepositorySave(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 1);

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $project = new Project();
        $project->setOwnerId($user->getId());
        $project->setName('Some project');
        $projectRepo->save($project);

        $this->assertEquals(3, $project->getId());
    }

    public function testWithIncludeSoftDeleted(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var UserRepository $userRepo */
        $userRepo = $db->getRepository(User::class);

        // user exists in the database
        $user = $userRepo->findOne(1);
        $this->assertNotNull($user);

        $db->softDelete($user);

        // user is soft deleted
        $user = $userRepo->findOne(1);
        $this->assertNull($user);

        // create a new repository with include soft deleted
        $userRepo = $userRepo->withIncludeSoftDeleted(true);

        // the user is findable again
        $user = $userRepo->findOne(1);
        $this->assertNotNull($user);
    }
}
