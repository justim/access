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

use Access\Exception;
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

    public function testSelectVirtualEntity(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualUserNames();
        $names = iterator_to_array($names, false);

        $names = array_map(fn($name) => $name->getUserName(), $names);

        $this->assertEquals(['Dave', 'Bob'], $names);
    }

    public function testSelectBrokenVirtualEntity(): void
    {
        $db = self::createDatabaseWithDummyData();

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
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->assertEquals('Dave', $names[1]['user_name']);
        $this->assertEquals(1, $names[1]['user_id']);
        $this->assertIsInt($names[1]['user_id']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $names[1]['user_created_at']);
    }

    public function testSelectVirtualEntityIsset(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->assertTrue(isset($names[1]['user_name']));
        $this->assertFalse(isset($names[1]['some_other_field']));
    }

    public function testSelectVirtualEntitySingleField(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNamesSingleField();
        $name = $names->first();

        $this->assertEquals('Dave', $name['user_name']);
    }

    public function testSelectVirtualEntityMissingField(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Field "some_other_field" not available');

        $names[1]['some_other_field'];
    }

    public function testSelectVirtualArrayEntityIllegalSet(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to update virtual array entities');

        $names[1]['user_name'] = 'Bob';
    }

    public function testSelectVirtualArrayEntityIllegalUnset(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $names = $projectRepo->findVirtualArrayUserNames();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to update virtual array entities');

        unset($names[1]['user_name']);
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
