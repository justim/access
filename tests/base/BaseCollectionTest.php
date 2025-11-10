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

use Access\Batch;
use Access\Collection;
use Access\Exception;
use Access\Clause;
use Access\Query\Select;
use PHPUnit\Framework\TestCase;

use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\Repository\ProjectRepository;
use Tests\Fixtures\Repository\UserRepository;

abstract class BaseCollectionTest extends TestCase implements DatabaseBuilderInterface
{
    public function testBatch(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findInProgress();

        $batchCount = 0;
        $projectCount = 0;

        /** @var Batch $batch */
        foreach ($projects as $batch) {
            $batchCount++;

            /** @var User[] $users */
            $users = $batch->findRefs(User::class, function (Project $project) {
                return $project->getOwnerId();
            });

            $this->assertEquals('Dave', $users[1]->getName());

            /** @var Project $project */
            foreach ($batch as $projectId => $project) {
                $projectCount++;

                $this->assertEquals($projectId, $project->getId());
            }
        }

        $this->assertEquals(1, $batchCount);
        $this->assertEquals(2, $projectCount);
    }

    public function testCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $ids = $projects->getIds();

        $this->assertEquals([1, 2], $ids);
        $this->assertTrue(isset($projects[1]));
        $this->assertEquals(2, $projects->count());

        /** @var Project $project */
        $project = $projects->find(function (Project $project) {
            return $project->getName() === 'Access';
        });

        $this->assertNotNull($project);
        $this->assertEquals('Access', $project->getName());

        /** @var Project|null $project */
        $project = $projects->find(function (Project $project) {
            return $project->getName() === 'NO NO NO';
        });

        $this->assertNull($project);
    }

    public function testEmptyCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findNothing();

        $this->assertEquals(0, count($projects));

        /** @var User[] $users */
        $users = $projects->findRefs(User::class, function (Project $project) {
            return $project->getOwnerId();
        });

        $this->assertEquals(0, count($users));
        $this->assertFalse(isset($users[1]));
    }

    public function testMergeCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findNothing();

        $this->assertEquals(0, count($projects));

        $projects->merge($projectRepo->findAllCollection());

        $this->assertEquals(2, count($projects));
    }

    public function testGroupByCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $this->assertEquals(2, count($projects));

        $grouped = $projects->groupBy(function (Project $project) {
            return $project->getOwnerId();
        });

        $this->assertEquals(2, count($grouped));

        $lastOwnerId = null;

        foreach ($grouped as $ownerId => $projectsPerOwner) {
            $this->assertTrue($ownerId > 0);
            $this->assertEquals(1, count($projectsPerOwner));

            $lastOwnerId = $ownerId;
        }

        /**
         * SAFEFY the loop has run
         * @var int $lastOwnerId
         */

        $this->assertNotNull($grouped[$lastOwnerId]);
        $this->assertNull($grouped[99999999]);
    }

    public function testSortCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $ids = $projects->getIds();
        $this->assertEquals([1, 2], $ids);

        $projects->sort(function (Project $one, Project $two) {
            return $two->getId() <=> $one->getId();
        });

        $sortedIds = $projects->getIds();
        $this->assertEquals([2, 1], $sortedIds);
    }

    public function testMapCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $names = $projects->map(function (Project $project) {
            return $project->getName();
        });

        $this->assertEquals(['Access', 'Access fork'], $names);
    }

    public function testReduceCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $idSum = $projects->reduce(function (int $carry, Project $project) {
            return $carry + $project->getId();
        }, 0);

        $this->assertEquals(3, $idSum);
    }

    public function testFromIterableCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAll();

        $collection = new Collection($db);

        $this->assertEquals(0, count($collection));

        // consume the generator
        $collection->fromIterable($projects);

        $this->assertEquals(2, count($collection));
    }

    public function testCollectionNoSet(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to remove entities through array access');

        unset($projects[1]);
    }

    public function testCollectionNoUnset(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $this->assertEquals(2, count($projects));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to add new entities through array access');

        $projects[3] = new Project();
    }

    public function testGroupedCollectionNoSet(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to remove collections through array access');

        $grouped = $projects->groupBy(function (Project $project) {
            return $project->getOwnerId();
        });

        unset($grouped[1]);
    }

    public function testGroupedCollectionNoUnset(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $this->assertEquals(2, count($projects));

        $grouped = $projects->groupBy(function (Project $project) {
            return $project->getOwnerId();
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to add new collections through array access');

        /** @var Collection<Project> $collection */
        $collection = new Collection($db);
        $grouped[3] = $collection;
    }

    public function testInversedRefs(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var UserRepository $userRepo */
        $userRepo = $db->getRepository(User::class);
        $users = $userRepo->findAllCollection();

        $this->assertEquals(2, count($users));

        $projects = $users->findInversedRefs(Project::class, 'owner_id');

        $this->assertEquals(2, count($projects));
    }

    public function testInversedRefsEmpty(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var UserRepository $userRepo */
        $userRepo = $db->getRepository(User::class);
        $users = $userRepo->findNothing();

        $this->assertEquals(0, count($users));

        $projects = $users->findInversedRefs(Project::class, 'owner_id');

        $this->assertEquals(0, count($projects));
    }

    public function testInversedRefsInvalidFieldName(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var UserRepository $userRepo */
        $userRepo = $db->getRepository(User::class);
        $users = $userRepo->findNothing();

        $this->assertEquals(0, count($users));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown field name for inversed refs');

        $users->findInversedRefs(Project::class, 'some_invalid_id');
    }

    public function testSelectQueryWithCollection(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var UserRepository $userRepo */
        $userRepo = $db->getRepository(User::class);
        $users = $userRepo->findAllCollection();

        $this->assertEquals(2, count($users));

        $query = new Select(User::class);
        $query->where('id IN (?)', $users);
        $newUsers = iterator_to_array($db->select(User::class, $query), false);

        $this->assertEquals(2, count($newUsers));
    }

    public function testCollectionFirst(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $this->assertEquals(2, count($projects));

        $fakeId = 1;
        foreach ($projects as $project) {
            $this->assertEquals($fakeId, $project->getId());

            $fakeId++;
        }

        $firstProject = $projects->first();

        $this->assertNotNull($firstProject);
        $this->assertEquals(1, $firstProject->getId());

        $emptyCollection = $db->createCollection();
        $emptyFirst = $emptyCollection->first();

        $this->assertNull($emptyFirst);
    }

    public function testCollectionContains(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 1);

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();
        $project = $projectRepo->findOne(1);

        $this->assertTrue($projects->contains($project));

        $project = new Project();
        $project->setOwnerId($user->getId());
        $db->save($project);

        $this->assertFalse($projects->contains($project));
    }

    public function testCollectionHasEntityWith(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $this->assertTrue($projects->hasEntityWith('id', 1));
        $this->assertFalse($projects->hasEntityWith('id', 3));
        $this->assertTrue($projects->hasEntityWith('name', 'Access'));
        $this->assertFalse($projects->hasEntityWith('name', 'Some project'));
        $this->assertFalse($projects->hasEntityWith('some_field', 'Access'));
    }

    public function testCollectionEmptyFirst(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findNothing();

        $this->assertEquals(0, count($projects));

        $firstProject = $projects->first();

        $this->assertNull($firstProject);
    }

    public function testSimpleOrderClause(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $ids = $projects->getIds();
        $this->assertEquals([1, 2], $ids);

        $sortedProjects = $projects->applyClause(new Clause\OrderBy\Descending('id'));

        $sortedIds = $sortedProjects->getIds();
        $this->assertEquals([2, 1], $sortedIds);
    }

    public function testSimpleConditionClause(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $ids = $projects->getIds();
        $this->assertEquals([1, 2], $ids);

        $filteredProjects = $projects->applyClause(new Clause\Condition\Equals('owner_id', 1));

        $filteredIds = $filteredProjects->getIds();
        $this->assertEquals([1], $filteredIds);
    }

    public function testMultiClause(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $ids = $projects->getIds();
        $this->assertEquals([1, 2], $ids);

        $filteredProjects = $projects->applyClause(
            new Clause\Multiple(
                new Clause\OrderBy\Descending('id'),
                new Clause\Condition\GreaterThan('id', 0),
            ),
        );

        $filteredIds = $filteredProjects->getIds();
        $this->assertEquals([2, 1], $filteredIds);
    }

    public function testDeduplication(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var UserRepository $userRepo */
        $userRepo = $db->getRepository(User::class);
        $users = $userRepo->findDuplicates();

        $this->assertEquals(4, $users->count());

        $deduplicated = $users->deduplicate();

        $this->assertEquals(2, $deduplicated->count());

        $deduplicated = $users->deduplicate('name');

        $this->assertEquals(2, $deduplicated->count());

        $deduplicated = $users->deduplicate('non-existent');

        $this->assertEquals(0, $deduplicated->count());
    }

    public function testSimpleLimit(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $ids = $projects->getIds();
        $this->assertEquals([1, 2], $ids);

        $limitedProjects = $projects->applyClause(new Clause\Limit(1));

        $limitedIds = $limitedProjects->getIds();
        $this->assertEquals([1], $limitedIds);
    }

    public function testMultipleLimit(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->findAllCollection();

        $ids = $projects->getIds();
        $this->assertEquals([1, 2], $ids);

        $limitedProjects = $projects->applyClause(
            new Clause\Multiple(new Clause\Limit(1, 1), new Clause\Limit(1)),
        );

        $limitedIds = $limitedProjects->getIds();
        $this->assertEquals([2], $limitedIds);
    }
}
