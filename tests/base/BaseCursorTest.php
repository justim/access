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

namespace Tests\Base;

use Access\Batch;
use Access\Query\Cursor\CurrentIdsCursor;
use Access\Query\Cursor\MaxValueCursor;
use Access\Query\Cursor\MinValueCursor;
use Access\Query\Cursor\PageCursor;
use Access\Query\Select;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Repository\ProjectRepository;
use Tests\Sqlite\AbstractBaseTestCase;

abstract class BaseCursorTest extends TestCase implements DatabaseBuilderInterface
{
    public function testSelectPageCursor(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $profiler = $db->getProfiler();
        $profiler->clear();

        $query = new Select(Project::class);
        $query->orderBy('id ASC');

        $projects = $projectRepo->selectPaginated($query, 1);

        $projectCount = 0;

        foreach ($projects as $project) {
            $this->assertInstanceOf(Project::class, $project);

            $projectCount++;
        }

        $this->assertEquals(2, $projectCount);

        $expectedQueries = [
            'SELECT `projects`.* FROM `projects` ORDER BY id ASC LIMIT 1 OFFSET 0',
            'SELECT `projects`.* FROM `projects` ORDER BY id ASC LIMIT 1 OFFSET 1',
            'SELECT `projects`.* FROM `projects` ORDER BY id ASC LIMIT 1 OFFSET 2',
        ];

        foreach ($profiler->export()['queries'] as $query) {
            $this->assertEquals($query['sql'], array_shift($expectedQueries));
        }

        // all queries should be used
        $this->assertCount(0, $expectedQueries);
    }

    public function testBatchedPageCursor(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $profiler = $db->getProfiler();
        $profiler->clear();

        $query = new Select(Project::class);
        $query->orderBy('id ASC');

        $projects = $projectRepo->selectBatchedPaginated($query, 1);

        $batchCount = 0;
        $projectCount = 0;

        foreach ($projects as $batch) {
            $this->assertInstanceOf(Batch::class, $batch);

            $batchCount++;

            foreach ($batch as $project) {
                $this->assertInstanceOf(Project::class, $project);

                $projectCount++;
            }
        }

        $this->assertEquals(2, $batchCount);
        $this->assertEquals(2, $projectCount);

        $expectedQueries = [
            'SELECT `projects`.* FROM `projects` ORDER BY id ASC LIMIT 1 OFFSET 0',
            'SELECT `projects`.* FROM `projects` ORDER BY id ASC LIMIT 1 OFFSET 1',
            'SELECT `projects`.* FROM `projects` ORDER BY id ASC LIMIT 1 OFFSET 2',
        ];

        foreach ($profiler->export()['queries'] as $query) {
            $this->assertEquals($query['sql'], array_shift($expectedQueries));
        }

        // all queries should be used
        $this->assertCount(0, $expectedQueries);
    }

    public function testSelectCurrentIdsCursor(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $profiler = $db->getProfiler();
        $profiler->clear();

        $query = new Select(Project::class);
        $query->orderBy('RANDOM()');

        $projects = $projectRepo->selectCurrentIdsCursor($query, 1);

        $projectCount = 0;

        foreach ($projects as $project) {
            $this->assertInstanceOf(Project::class, $project);

            $projectCount++;
        }

        $this->assertEquals(2, $projectCount);

        $expectedQueries = [
            'SELECT `projects`.* FROM `projects` ORDER BY RAND() LIMIT 1',
            'SELECT `projects`.* FROM `projects` WHERE `projects`.`id` NOT IN (:w0) ORDER BY RAND() LIMIT 1',
            'SELECT `projects`.* FROM `projects` WHERE `projects`.`id` NOT IN (:w0, :w1) ORDER BY RAND() LIMIT 1',
        ];

        foreach ($profiler->export()['queries'] as $query) {
            $this->assertEquals($query['sql'], array_shift($expectedQueries));
        }

        // all queries should be used
        $this->assertCount(0, $expectedQueries);
    }

    public function testBatchedCurrentIdsCursor(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $profiler = $db->getProfiler();
        $profiler->clear();

        $query = new Select(Project::class);
        $query->orderBy('RANDOM()');

        $projects = $projectRepo->selectBatchedCurrentIdsCursor($query, 1);

        $batchCount = 0;
        $projectCount = 0;

        foreach ($projects as $batch) {
            $this->assertInstanceOf(Batch::class, $batch);

            $batchCount++;

            foreach ($batch as $project) {
                $this->assertInstanceOf(Project::class, $project);

                $projectCount++;
            }
        }

        $this->assertEquals(2, $batchCount);
        $this->assertEquals(2, $projectCount);

        $expectedQueries = [
            'SELECT `projects`.* FROM `projects` ORDER BY RAND() LIMIT 1',
            'SELECT `projects`.* FROM `projects` WHERE `projects`.`id` NOT IN (:w0) ORDER BY RAND() LIMIT 1',
            'SELECT `projects`.* FROM `projects` WHERE `projects`.`id` NOT IN (:w0, :w1) ORDER BY RAND() LIMIT 1',
        ];

        foreach ($profiler->export()['queries'] as $query) {
            $this->assertEquals($query['sql'], array_shift($expectedQueries));
        }

        // all queries should be used
        $this->assertCount(0, $expectedQueries);
    }

    public function testBatchedMinValueCursor(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $profiler = $db->getProfiler();
        $profiler->clear();

        $query = new Select(Project::class);
        $query->orderBy('id DESC');

        $projects = $projectRepo->selectBatchedMinValueCursor($query, 1);

        $batchCount = 0;
        $projectCount = 0;

        foreach ($projects as $batch) {
            $this->assertInstanceOf(Batch::class, $batch);

            $batchCount++;

            foreach ($batch as $project) {
                $this->assertInstanceOf(Project::class, $project);

                $projectCount++;
            }
        }

        $this->assertEquals(2, $batchCount);
        $this->assertEquals(2, $projectCount);

        $expectedQueries = [
            'SELECT `projects`.* FROM `projects` ORDER BY id DESC LIMIT 1',
            'SELECT `projects`.* FROM `projects` WHERE `projects`.`id` < :w0 ORDER BY id DESC LIMIT 1',
            'SELECT `projects`.* FROM `projects` WHERE `projects`.`id` < :w0 ORDER BY id DESC LIMIT 1',
        ];

        $expectedValues = [[], ['w0' => 2], ['w0' => 1]];

        foreach ($profiler->export()['queries'] as $query) {
            $this->assertEquals($query['sql'], array_shift($expectedQueries));
            $this->assertEquals($query['values'], array_shift($expectedValues));
        }

        // all queries should be used
        $this->assertCount(0, $expectedQueries);
        $this->assertCount(0, $expectedValues);
    }

    public function testBatchedMaxValueCursor(): void
    {
        $db = static::createDatabaseWithDummyData();

        /** @var ProjectRepository $projectRepo */
        $projectRepo = $db->getRepository(Project::class);

        $profiler = $db->getProfiler();
        $profiler->clear();

        $query = new Select(Project::class);
        $query->orderBy('id ASC');

        $projects = $projectRepo->selectBatchedMaxValueCursor($query, 1);

        $batchCount = 0;
        $projectCount = 0;

        foreach ($projects as $batch) {
            $this->assertInstanceOf(Batch::class, $batch);

            $batchCount++;

            foreach ($batch as $project) {
                $this->assertInstanceOf(Project::class, $project);

                $projectCount++;
            }
        }

        $this->assertEquals(2, $batchCount);
        $this->assertEquals(2, $projectCount);

        $expectedQueries = [
            'SELECT `projects`.* FROM `projects` ORDER BY id ASC LIMIT 1',
            'SELECT `projects`.* FROM `projects` WHERE `projects`.`id` > :w0 ORDER BY id ASC LIMIT 1',
            'SELECT `projects`.* FROM `projects` WHERE `projects`.`id` > :w0 ORDER BY id ASC LIMIT 1',
        ];

        $expectedValues = [[], ['w0' => 1], ['w0' => 2]];

        foreach ($profiler->export()['queries'] as $query) {
            $this->assertEquals($query['sql'], array_shift($expectedQueries));
            $this->assertEquals($query['values'], array_shift($expectedValues));
        }

        // all queries should be used
        $this->assertCount(0, $expectedQueries);
        $this->assertCount(0, $expectedValues);
    }
}
