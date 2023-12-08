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
use Tests\Fixtures\Entity\InvalidEnumNameEntity;
use Tests\Fixtures\Entity\MissingEnumNameEntity;
use Tests\Fixtures\Entity\MissingPublicSoftDeleteEntity;
use Tests\Fixtures\Entity\MissingSetDeletedEntity;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\Repository\ProjectRepository;
use Tests\Fixtures\UserStatus;

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

    public function testEnumValue(): void
    {
        $db = self::createDatabase();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');
        $dave->setStatus(UserStatus::ACTIVE);
        $db->insert($dave);

        /** @var User $user */
        $user = $db->findOne(User::class, $dave->getId());

        $this->assertEquals(UserStatus::ACTIVE, $user->getStatus());

        $dave->setStatus(UserStatus::BANNED);
        $db->save($dave);

        /** @var User $user */
        $user = $db->findOne(User::class, $dave->getId());

        $this->assertEquals(UserStatus::BANNED, $user->getStatus());
    }

    public function testMissingEnumName(): void
    {
        $db = self::createDatabase();

        $entity = new MissingEnumNameEntity();
        $entity->setStatus(UserStatus::ACTIVE);

        $db->save($entity);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing enum name for field "status"');

        // hydrating fails
        $db->findOne(MissingEnumNameEntity::class, $entity->getId());
    }

    public function testInvalidEnumName(): void
    {
        $db = self::createDatabase();

        $entity = new InvalidEnumNameEntity();
        $entity->setStatus(UserStatus::ACTIVE);

        $db->save($entity);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Invalid enum name for field "status": Tests\Fixtures\Entity\InvalidEnumNameEntity',
        );

        // hydrating fails
        $db->findOne(InvalidEnumNameEntity::class, $entity->getId());
    }

    public function testMissingPublicSoftDelete(): void
    {
        $db = self::createDatabase();

        $entity = new MissingPublicSoftDeleteEntity();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Soft delete method is not public');

        $db->softDelete($entity);
    }

    public function testMissingSetDeleted(): void
    {
        $db = self::createDatabase();

        $entity = new MissingSetDeletedEntity();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Entity is not soft deletable');

        $db->softDelete($entity);
    }
}
