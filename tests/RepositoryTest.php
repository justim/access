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
    /**
     * @depends testInsert
     */
    public function testSelectOne(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);

        /** @var Project $project */
        $project = $projectRepo->findByName('Access');

        $this->assertNotNull($project);
        $this->assertEquals('Access', $project->getName());

        $project = $projectRepo->findByName('BLABLA');

        $this->assertNull($project);
    }

    /**
     * @depends testInsert
     */
    public function testFindAllWithLimit(): void
    {
        $projects = self::$db->findAll(Project::class, 1);

        foreach ($projects as $project) {
            $this->assertEquals('Access', $project->getName());
        }
    }

    /**
     * @depends testInsert
     */
    public function testDirectQuery(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);

        /** @var Project $project */
        $project = $projectRepo->findOne(1);

        $projectRepo->setNameWithDirectQuery($project->getId(), 'Access2');

        /** @var Project $project */
        $project = $projectRepo->findOne(1);
        $this->assertEquals('Access2', $project->getName());
    }

    /**
     * @depends testInsert
     */
    public function testFindByEmptyIds(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);

        $projects = $projectRepo->findByIds([]);

        $this->assertEquals(
            0,
            count(iterator_to_array($projects)),
        );
    }

    /**
     * @depends testInsert
     */
    public function testSelectVirtualField(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);

        $total = $projectRepo->findTotalCount();

        $this->assertEquals(
            2,
            $total,
        );
    }
}
