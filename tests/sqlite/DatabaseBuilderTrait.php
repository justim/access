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

namespace Tests\Sqlite;

use Access\Database;
use Access\Query\Raw;
use Psr\Clock\ClockInterface;

use Tests\Fixtures\Entity\ProfileImage;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\MockClock;

trait DatabaseBuilderTrait
{
    private static function createTables(Database $db): Database
    {
        $db->query(new Raw('PRAGMA foreign_keys = ON'));

        $createProfileImagesQuery = new Raw('CREATE TABLE `profile_images` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `created_at` DATETIME,
            `updated_at` DATETIME
        )');

        $db->query($createProfileImagesQuery);

        $createUsersQuery = new Raw('CREATE TABLE `users` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `role` VARCHAR(20) DEFAULT NULL,
            `profile_image_id` INTEGER DEFAULT NULL,
            `status` VARCHAR(20) DEFAULT NULL,
            `name` VARCHAR(50) DEFAULT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `created_at` DATETIME,
            `updated_at` DATETIME,
            `deleted_at` DATETIME DEFAULT NULL,
            FOREIGN KEY(profile_image_id) REFERENCES profile_images(id)
        )');

        $db->query($createUsersQuery);

        $createProjectsQuery = new Raw('CREATE TABLE `projects` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `status` VARCHAR(20) DEFAULT NULL,
            `owner_id` INTEGER NOT NULL,
            `name` VARCHAR(50) DEFAULT NULL,
            `published_at` DATE DEFAULT NULL,
            `created_at` DATETIME,
            `updated_at` DATETIME,
            FOREIGN KEY(owner_id) REFERENCES users(id)
        )');

        $db->query($createProjectsQuery);

        $createLogMessagesQuery = new Raw('CREATE TABLE `log_messages` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `message` VARCHAR(100) DEFAULT NULL,
            `created_at` DATETIME
        )');

        $db->query($createLogMessagesQuery);

        return $db;
    }

    public static function createEmptyDatabase(): Database
    {
        $db = Database::create('sqlite::memory:');

        return $db;
    }

    public static function createDatabase(): Database
    {
        $db = self::createEmptyDatabase();

        return self::createTables($db);
    }

    public static function createDatabaseWithMockClock(?ClockInterface $clock = null): Database
    {
        $clock = $clock ?? new MockClock();
        $db = Database::create('sqlite::memory:', null, $clock);

        return self::createTables($db);
    }

    /**
     * Create a dummy database with:
     *
     * - 1 profile image
     * - 2 users
     * - 2 projects
     */
    public static function createDatabaseWithDummyData(): Database
    {
        $db = self::createDatabase();

        $profileImage = new ProfileImage();
        $db->save($profileImage);

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');
        $dave->setProfileImageId($profileImage->getId());
        $db->insert($dave);

        $bob = new User();
        $bob->setEmail('bob@example.com');
        $bob->setName('Bob');
        $db->insert($bob);

        $access = new Project();
        $access->setOwnerId($dave->getId());
        $access->setName('Access');
        $access->setPublishedAt(\DateTime::createFromFormat('Y-m-d', '2019-02-07') ?: null);
        $db->save($access);

        $accessFork = new Project();
        $accessFork->setOwnerId($bob->getId());
        $accessFork->setName('Access fork');
        $db->insert($accessFork);

        return $db;
    }
}
