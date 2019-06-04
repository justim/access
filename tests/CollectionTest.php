<?php

declare(strict_types=1);

namespace Tests;

use Access\Batch;
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
        $projects = $projectRepo->findAll();

        $ids = $projects->getIds();

        $this->assertEquals([1, 2], $ids);
        $this->assertTrue(isset($projects[1]));

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
    public function testCollectionNoSet(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);
        $projects = $projectRepo->findAll();

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
        $projects = $projectRepo->findAll();

        $this->assertEquals(2, count($projects));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to add new entities through array access');

        $projects[3] = new Project();
    }
}
