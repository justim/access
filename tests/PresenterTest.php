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

use Access\Presenter;
use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\Presenter\PlainProjectPresenter;
use Tests\Fixtures\Presenter\ProjectPresenter;
use Tests\Fixtures\StatusFormatter;

class PresenterTest extends AbstractBaseTestCase
{
    public function testInsert(): void
    {
        // override test insert, we dont need it here
        $this->assertTrue(true);
    }

    public function testPresenterEntity(): void
    {
        $user = new User();
        self::$db->save($user);

        $project = new Project();
        $project->setOwnerId($user);
        self::$db->save($project);

        $profiler = self::$db->getProfiler();
        $currentNumQueries = $profiler->count();

        $expected = [
            'id' => $project->getId(),
        ];

        $result = self::$db->presentEntity(PlainProjectPresenter::class, $project);

        $this->assertEquals($expected, $result);

        $numQueries = $profiler->count() - $currentNumQueries;
        $this->assertEquals(0, $numQueries);
    }

    /**
     * @depends testPresenterEntity
     */
    public function testPresenterEntityWithDependency(): void
    {
        $project = self::$db->findOne(Project::class, 1);

        $profiler = self::$db->getProfiler();
        $currentNumQueries = $profiler->count();

        $expected = [
            'id' => 1,
            'owner' => [
                'id' => 1,
                'projects' => [
                    [
                        'id' => 1,
                    ],
                ],
            ],
            'ownerFuture' => [
                'id' => 1,
                'projects' => [
                    [
                        'id' => 1,
                    ],
                ],
            ],
            'status' => 'In progress',
        ];

        $statusFormatter = new StatusFormatter();

        $presenter = new Presenter(self::$db);
        $presenter->addDependency($statusFormatter);
        $result = $presenter->presentEntity(ProjectPresenter::class, $project);

        $this->assertEquals($expected, $result);

        $numQueries = $profiler->count() - $currentNumQueries;
        $this->assertEquals(2, $numQueries);
    }

    /**
     * @depends testPresenterEntityWithDependency
     */
    public function testPresenterCollectionWithDependency(): void
    {
        $user = new User();
        self::$db->save($user);

        $project = new Project();
        $project->setOwnerId($user->getId());
        self::$db->save($project);

        $projects = self::$db->getRepository(Project::class)->findAllCollection();

        $profiler = self::$db->getProfiler();
        $currentNumQueries = $profiler->count();

        $expected = [
            [
                'id' => 1,
                'owner' => [
                    'id' => 1,
                    'projects' => [
                        [
                            'id' => 1,
                        ],
                    ],
                ],
                'ownerFuture' => [
                    'id' => 1,
                    'projects' => [
                        [
                            'id' => 1,
                        ],
                    ],
                ],
                'status' => 'In progress',
            ],
            [
                'id' => 2,
                'owner' => [
                    'id' => 2,
                    'projects' => [
                        [
                            'id' => 2,
                        ],
                    ],
                ],
                'ownerFuture' => [
                    'id' => 2,
                    'projects' => [
                        [
                            'id' => 2,
                        ],
                    ],
                ],
                'status' => 'In progress',
            ],
        ];

        $statusFormatter = new StatusFormatter();

        $presenter = new Presenter(self::$db);
        $presenter->addDependency($statusFormatter);
        $result = $presenter->presentCollection(ProjectPresenter::class, $projects);

        $this->assertEquals($expected, $result);

        $numQueries = $profiler->count() - $currentNumQueries;
        $this->assertEquals(2, $numQueries);
    }
}
