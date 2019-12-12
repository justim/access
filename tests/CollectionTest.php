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

use Access\Batch;
use Access\Collection;
use Access\Exception;

use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\Repository\ProjectRepository;

class CollectionTest extends AbstractBaseTestCase
{
    /**
     * @depends testInsert
     */
    public function testBatch(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
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

    /**
     * @depends testInsert
     */
    public function testCollection(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findAllAsCollection();

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

    /**
     * @depends testInsert
     */
    public function testEmptyCollection(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findNothing();

        $this->assertEquals(0, count($projects));

        /** @var User[] $users */
        $users = $projects->findRefs(User::class, function (Project $project) {
            return $project->getOwnerId();
        });

        $this->assertEquals(0, count($users));
        $this->assertFalse(isset($users[1]));
    }

    /**
     * @depends testInsert
     */
    public function testMergeCollection(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findNothing();

        $this->assertEquals(0, count($projects));

        $projects->merge($projectRepo->findAllAsCollection());

        $this->assertEquals(2, count($projects));
    }

    /**
     * @depends testInsert
     */
    public function testGroupByCollection(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findAllAsCollection();

        $this->assertEquals(2, count($projects));

        $grouped = $projects->groupBy(function (Project $project) {
            return $project->getOwnerId();
        });

        $this->assertEquals(2, count($grouped));

        foreach ($grouped as $ownerId => $projectsPerOwner) {
            $this->assertTrue($ownerId > 0);
            $this->assertEquals(1, count($projectsPerOwner));
        }
    }

    /**
     * @depends testInsert
     */
    public function testSortCollection(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findAllAsCollection();

        $ids = $projects->getIds();
        $this->assertEquals([1, 2], $ids);

        $projects->sort(function (Project $one, Project $two) {
            return $two->getId() <=> $one->getId();
        });

        $sortedIds = $projects->getIds();
        $this->assertEquals([2, 1], $sortedIds);
    }

    /**
     * @depends testInsert
     */
    public function testMapCollection(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findAllAsCollection();

        $names = $projects->map(function (Project $project) {
            return $project->getName();
        });

        $this->assertEquals(['Access', 'Access fork'], $names);
    }

    /**
     * @depends testInsert
     */
    public function testFromIterableCollection(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findAll();

        $collection = new Collection(self::$db);

        $this->assertEquals(0, count($collection));

        // consume the generator
        $collection->fromIterable($projects);

        $this->assertEquals(2, count($collection));
    }

    /**
     * @depends testInsert
     */
    public function testCollectionNoSet(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findAllAsCollection();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to remove entities through array access');

        unset($projects[1]);
    }

    /**
     * @depends testInsert
     */
    public function testCollectionNoUnset(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findAllAsCollection();

        $this->assertEquals(2, count($projects));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to add new entities through array access');

        $projects[3] = new Project();
    }
}
