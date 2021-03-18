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

use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Repository\ProjectRepository;

class RepositoryTest extends AbstractBaseTestCase
{
    public function testSelectOne(): void
    {
        $db = self::createDatabaseWithDummyData();

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
        $db = self::createDatabaseWithDummyData();

        $projects = $db->findAll(Project::class, 1);

        foreach ($projects as $project) {
            $this->assertEquals('Access', $project->getName());
        }
    }

    public function testDirectQuery(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        /** @var Project $project */
        $project = $projectRepo->findOne(1);

        $projectRepo->setNameWithDirectQuery($project->getId(), 'Access2');

        /** @var Project $project */
        $project = $projectRepo->findOne(1);
        $this->assertEquals('Access2', $project->getName());
    }

    public function testFindByEmptyIds(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projects = $projectRepo->findByIds([]);

        $this->assertEquals(0, count(iterator_to_array($projects)));
    }

    public function testSelectVirtualField(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $total = $projectRepo->findTotalCount();

        $this->assertEquals(2, $total);
    }

    public function testSelectAddedVirtualField(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $total = $projectRepo->findTotalCountAdded();

        $this->assertEquals(2, $total);
    }

    public function testSelectReplacedVirtualField(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $total = $projectRepo->findTotalCountReplaced();

        $this->assertEquals(2, $total);
    }

    public function testSelectdBatched(): void
    {
        $db = self::createDatabaseWithDummyData();

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
}
