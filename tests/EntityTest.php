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
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\Repository\ProjectRepository;

class EntityTest extends AbstractBaseTestCase
{
    public function testIdAlreadySet(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ID already set');

        $user->setId(2);
    }

    public function testIdNotAvailable(): void
    {
        $user = new User();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ID not available yet');

        $user->getId();
    }

    public function testUnavailableField(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Field "username" not available');

        $user->getUsername();
    }

    public function testOverrideId(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to change ID');

        $user->overrideId(12);
    }

    public function testSimpleDeletedAt(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 1);

        $this->assertNull($user->getDeletedAt());

        $user->setDeletedAt();
        $db->save($user);

        $this->assertNotNull($user->getDeletedAt());

        $user = $db->findOne(User::class, 1);
        $this->assertNull($user);
    }

    public function testSimpleDeletedAtDatabaseHelper(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 2);

        $this->assertNull($user->getDeletedAt());

        $db->softDelete($user);

        $this->assertNotNull($user->getDeletedAt());

        $user = $db->findOne(User::class, 2);
        $this->assertNull($user);
    }

    public function testDeletedAtJoin(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var User $user */
        $user = $db->findOne(User::class, 1);
        $db->softDelete($user);
        $this->assertNotNull($user->getDeletedAt());

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projects = $projectRepo->findWithUserName();

        $this->assertEquals(1, count($projects));
    }

    public function testCopy(): void
    {
        $db = self::createDatabaseWithDummyData();

        $projectToCopyName = 'Access';
        $copyProjectName = 'Access copy';

        /** @var Project $project */
        $project = $db->findOneBy(Project::class, ['name' => $projectToCopyName]);

        $projectCopy = $project->copy();

        $projectCopy->setName($copyProjectName);

        $db->save($projectCopy);

        $this->assertNotEquals($project->getId(), $projectCopy->getId());
        $this->assertEquals($project->getName(), $projectToCopyName);
        $this->assertEquals($projectCopy->getName(), $copyProjectName);
        $this->assertEquals($project->getStatus(), $projectCopy->getStatus());

        $this->assertNotNull($project->getCreatedAt());
        $this->assertNotNull($projectCopy->getCreatedAt());

        $this->assertNotNull($project->getPublishedAt());
    }
}
