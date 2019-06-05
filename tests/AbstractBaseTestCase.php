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

use PHPUnit\Framework\TestCase;

use Access\Database;
use Access\Query\Raw;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

abstract class AbstractBaseTestCase extends TestCase
{
    /**
     * @var Database $db
     */
    protected static $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::create('sqlite::memory:');

        $createUsersQuery = new Raw('CREATE TABLE `users` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `role` VARCHAR(20) DEFAULT NULL,
            `name` VARCHAR(50) DEFAULT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `created_at` DATETIME,
            `updated_at` DATETIME
        )');

        self::$db->query($createUsersQuery);

        self::$db->query(new Raw('SELECT * FROM users'));

        $createProjectsQuery = new Raw('CREATE TABLE `projects` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `status` VARCHAR(20) DEFAULT NULL,
            `owner_id` INTEGER,
            `name` VARCHAR(50) DEFAULT NULL,
            `created_at` DATETIME,
            `updated_at` DATETIME
        )');

        self::$db->query($createProjectsQuery);
    }

    public static function tearDownAfterClass(): void
    {
        $dropUsersQuery = new Raw('DROP TABLE `users`');
        self::$db->query($dropUsersQuery);

        $dropProjectsQuery = new Raw('DROP TABLE `projects`');
        self::$db->query($dropProjectsQuery);
    }

    public function testInsert(): void
    {
        $dave = new User();
        $dave->setEmail('dave@example.php');
        $dave->setName('Dave');

        self::$db->insert($dave);

        $this->assertEquals(1, $dave->getId());
        $this->assertNotNull($dave->getCreatedAt());

        $bob = new User();
        $bob->setEmail('bob@example.php');
        $bob->setName('Bob');

        self::$db->insert($bob);

        $this->assertEquals(2, $bob->getId());
        $this->assertNotNull($bob->getCreatedAt());

        $access = new Project();
        $access->setOwnerId($dave->getId());
        $access->setName('Access');

        self::$db->insert($access);

        $this->assertEquals(1, $access->getId());
        $this->assertNotNull($access->getCreatedAt());

        $accessFork = new Project();
        $accessFork->setOwnerId($bob->getId());
        $accessFork->setName('Access fork');

        self::$db->insert($accessFork);

        $this->assertEquals(2, $accessFork->getId());
        $this->assertNotNull($accessFork->getCreatedAt());
    }
}
