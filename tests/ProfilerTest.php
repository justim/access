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

use Access\Database;
use Access\Profiler\BlackholeProfiler;
use Access\Query\Raw;

use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

class ProfilerTest extends AbstractBaseTestCase
{
    public function testProfiler(): void
    {
        $db = self::createDatabaseWithDummyData();

        $profiler = $db->getProfiler();
        $export = $profiler->export();

        $this->assertIsFloat($export['duration']);
        $this->assertIsFloat($profiler->getTotalDuration());

        // two create tables and four inserts
        $this->assertEquals(6, count($export['queries']));
    }

    public function testNumberOfResults(): void
    {
        $db = self::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $projectRepo->findOne(1);

        $profiler = $db->getProfiler();
        $export = $profiler->export();

        $lastProfile = array_pop($export['queries']);
        $this->assertEquals(1, $lastProfile['numberOfResults']);

        iterator_to_array($projectRepo->findAll(), false);

        $export = $profiler->export();

        $lastProfile = array_pop($export['queries']);
        $this->assertEquals(2, $lastProfile['numberOfResults']);
    }

    public function testBlackholeProfiler(): void
    {
        // create connection with blackhole profiler
        $db = Database::create('sqlite::memory:', new BlackholeProfiler());

        // do a bunch of queries on this connection..

        $createUsersQuery = new Raw('CREATE TABLE `users` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `role` VARCHAR(20) DEFAULT NULL,
            `name` VARCHAR(50) DEFAULT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `created_at` DATETIME,
            `updated_at` DATETIME,
            `deleted_at` DATETIME DEFAULT NULL
        )');
        $db->query($createUsersQuery);

        $users = $db->findAll(User::class);
        $this->assertEquals(0, count(iterator_to_array($users)));

        $profiler = $db->getProfiler();
        $export = $profiler->export();

        // .. but nothing is tracked, preserving precious memory

        $this->assertEquals(0.0, $export['duration']);
        $this->assertEquals(0.0, $profiler->getTotalDuration());
        $this->assertEquals(0, count($export['queries']));
    }
}
