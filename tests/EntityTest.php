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

class EntityTest extends AbstractBaseTestCase
{
    /**
     * @depends testInsert
     */
    public function testIdAlreadySet(): void
    {
        /** @var User $user */
        $user = self::$db->findOne(User::class, 1);

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

    /**
     * @depends testInsert
     */
    public function testUnavailableField(): void
    {
        /** @var User $user */
        $user = self::$db->findOne(User::class, 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Field "username" not available');

        $user->getUsername();
    }

    /**
     * @depends testInsert
     */
    public function testOverrideId(): void
    {
        /** @var User $user */
        $user = self::$db->findOne(User::class, 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not possible to change ID');

        $user->overrideId(12);
    }

    /**
     * @depends testInsert
     */
    public function testSimpleDeletedAt(): void
    {
        /** @var User $user */
        $user = self::$db->findOne(User::class, 1);

        $user->setDeletedAt();
        self::$db->save($user);

        $user = self::$db->findOne(User::class, 1);
        $this->assertNull($user);
    }

    /**
     * @depends testInsert
     */
    public function testDeletedAtJoin(): void
    {
        /** @var ProjectRepository $projectRepo */
        $projectRepo = self::$db->getRepository(Project::class);

        $projects = $projectRepo->findWithUserName();

        $this->assertEquals(1, count($projects));
    }

    /**
     * @depends testInsert
     */
    public function testCopy(): void
    {
        $projectToCopyName = 'Access';
        $copyProjectName = 'Access copy';

        /** @var Project $project */
        $project = self::$db->findOneBy(Project::class, ['name' => $projectToCopyName]);

        $projectCopy = $project->copy();

        $projectCopy->setName($copyProjectName);

        self::$db->save($projectCopy);

        $this->assertNotEquals($project->getId(), $projectCopy->getId());
        $this->assertEquals($project->getName(), $projectToCopyName);
        $this->assertEquals($projectCopy->getName(), $copyProjectName);
        $this->assertEquals($project->getStatus(), $projectCopy->getStatus());
    }
}
