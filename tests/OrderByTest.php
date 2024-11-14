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
use Access\Clause;
use Access\Clause\OrderBy\Ascending;
use Access\Clause\OrderBy\Random;
use Access\Query\Select;

use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\Repository\ProjectRepository;
use Tests\Fixtures\Repository\UserRepository;

class OrderByTest extends AbstractBaseTestCase
{
    public function testSimpleAscending(): void
    {
        $db = self::createDatabaseWithDummyData();

        $query = new Select(Project::class, 'p');
        $query->orderBy(new Ascending('p.name'));

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->selectCollection($query);

        $ids = $projects->getIds();
        $this->assertEquals([1, 2], $ids);
    }

    public function testRandom(): void
    {
        $db = self::createDatabaseWithDummyData();

        $query = new Select(Project::class, 'p');
        $query->orderBy(new Random());

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->selectCollection($query);

        $ids = $projects->getIds();
        $this->assertContains(1, $ids);
        $this->assertContains(2, $ids);
    }

    public function testSimpleConversion(): void
    {
        $db = self::createDatabaseWithDummyData();

        $query = new Select(Project::class, 'p');
        $query->orderBy('p.name DESC');

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);
        $projects = $projectRepo->selectCollection($query);

        $ids = $projects->getIds();
        $this->assertEquals([2, 1], $ids);
    }
}
