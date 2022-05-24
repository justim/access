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
use Access\Clause;
use Access\Collection;
use Access\Database;
use Access\Exception;
use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\Presenter\BrokenInfiniteLoopPresenter;
use Tests\Fixtures\Presenter\BrokenMissingDependencyPresenter;
use Tests\Fixtures\Presenter\BrokenMissingTypePresenter;
use Tests\Fixtures\Presenter\BrokenNonPublicReceiveDependenciesPresenter;
use Tests\Fixtures\Presenter\BrokenPresenter;
use Tests\Fixtures\Presenter\BrokenVariadicParametersPresenter;
use Tests\Fixtures\Presenter\BrokenWithoutEntityKlassPresenter;
use Tests\Fixtures\Presenter\PlainProjectPresenter;
use Tests\Fixtures\Presenter\PlainUserPresenter;
use Tests\Fixtures\Presenter\ProjectPresenter;
use Tests\Fixtures\Presenter\ProjectWithDatesPresenter;
use Tests\Fixtures\Presenter\ProjectWithEmptyPresenter;
use Tests\Fixtures\Presenter\ProjectWithOwnerPresenter;
use Tests\Fixtures\Presenter\ProjectWithReceiveDependenciesPresenter;
use Tests\Fixtures\Presenter\UserEmptyResultPresenter;
use Tests\Fixtures\Presenter\UserOptionalDependencyPresenter;
use Tests\Fixtures\Presenter\UserWithClausePresenter;
use Tests\Fixtures\Presenter\UserWithDatabasePresenter;
use Tests\Fixtures\Presenter\UserWithUserPresenter;
use Tests\Fixtures\StatusFormatter;

class PresenterTest extends AbstractBaseTestCase
{
    private const OPTION_SINGLE_PROJECT = 1;
    private const OPTION_EXTRA_USER = 2;

    /**
     * @return array{\Access\Database, User, Project, Project, User}
     */
    private function createAndSetupEntities(int $options = 0): array
    {
        $db = self::createDatabase();

        $userOne = new User();
        $db->save($userOne);

        $projectOne = new Project();
        $projectOne->setOwnerId($userOne);
        $projectOne->setName('one');
        $db->save($projectOne);

        if (($options & self::OPTION_EXTRA_USER) !== 0) {
            $userTwo = new User();
            $db->save($userTwo);
        } else {
            $userTwo = null;
        }

        if (($options & self::OPTION_SINGLE_PROJECT) === 0) {
            $projectTwo = new Project();
            $projectTwo->setOwnerId($userTwo ?? $userOne);
            $projectTwo->setName('two');
            $db->save($projectTwo);
        } else {
            $projectTwo = null;
        }

        // dummies to make sure we return according to signature for easy
        // access, the logic from the $options makes sure of it
        $userTwo ??= new User();
        $projectTwo ??= new Project();

        return [$db, $userOne, $projectOne, $projectTwo, $userTwo];
    }

    public function testPresenterEntity(): void
    {
        [$db, , $projectOne] = $this->createAndSetupEntities(self::OPTION_SINGLE_PROJECT);

        $profiler = $db->getProfiler();
        $currentNumQueries = $profiler->count();

        $expected = [
            'id' => $projectOne->getId(),
        ];

        $result = $db->presentEntity(PlainProjectPresenter::class, $projectOne);

        $this->assertEquals($expected, $result);

        $numQueries = $profiler->count() - $currentNumQueries;
        $this->assertEquals(0, $numQueries);
    }

    public function testPresenterEntityWithDependency(): void
    {
        [$db, $userOne, $projectOne] = $this->createAndSetupEntities(self::OPTION_SINGLE_PROJECT);

        $profiler = $db->getProfiler();
        $currentNumQueries = $profiler->count();

        $expected = [
            'id' => $projectOne->getId(),
            'owner' => [
                'id' => $userOne->getId(),
                'projects' => [
                    [
                        'id' => $projectOne->getId(),
                    ],
                ],
            ],
            'ownerFuture' => [
                'id' => $userOne->getId(),
                'projects' => [
                    [
                        'id' => $projectOne->getId(),
                    ],
                ],
            ],
            'status' => 'In progress',
        ];

        $statusFormatter = new StatusFormatter();

        $presenter = new Presenter($db);
        $presenter->addDependency($statusFormatter);
        $result = $presenter->presentEntity(ProjectPresenter::class, $projectOne);

        $this->assertEquals($expected, $result);

        $numQueries = $profiler->count() - $currentNumQueries;
        $this->assertEquals(2, $numQueries);
    }

    public function testPresenterCollectionWithDependency(): void
    {
        [$db, $userOne, $projectOne, $projectTwo, $userTwo] = $this->createAndSetupEntities(
            self::OPTION_EXTRA_USER,
        );

        $projects = $db->getRepository(Project::class)->findAllCollection();

        $profiler = $db->getProfiler();
        $currentNumQueries = $profiler->count();

        $expected = [
            [
                'id' => $projectOne->getId(),
                'owner' => [
                    'id' => $userOne->getId(),
                    'projects' => [
                        [
                            'id' => $projectOne->getId(),
                        ],
                    ],
                ],
                'ownerFuture' => [
                    'id' => $userOne->getId(),
                    'projects' => [
                        [
                            'id' => $projectOne->getId(),
                        ],
                    ],
                ],
                'status' => 'In progress',
            ],
            [
                'id' => $projectTwo->getId(),
                'owner' => [
                    'id' => $userTwo->getId(),
                    'projects' => [
                        [
                            'id' => $projectTwo->getId(),
                        ],
                    ],
                ],
                'ownerFuture' => [
                    'id' => $userTwo->getId(),
                    'projects' => [
                        [
                            'id' => $projectTwo->getId(),
                        ],
                    ],
                ],
                'status' => 'In progress',
            ],
        ];

        $statusFormatter = new StatusFormatter();

        $presenter = new Presenter($db);
        $presenter->addDependency($statusFormatter);
        $result = $presenter->presentCollection(ProjectPresenter::class, $projects);

        $this->assertEquals($expected, $result);

        $numQueries = $profiler->count() - $currentNumQueries;
        $this->assertEquals(2, $numQueries);
    }

    public function testSimpleOrderByIdClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\OrderBy\Ascending('id'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\OrderBy\Descending('id'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleOrderByNameClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\OrderBy\Ascending('name'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\OrderBy\Descending('name'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleOrderByOwnerIdClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\OrderBy\Ascending('owner_id'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\OrderBy\Descending('owner_id'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleOrderByMissingFieldClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\OrderBy\Ascending('some_field'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\OrderBy\Descending('some_field'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleEqualsIdClause(): void
    {
        [$db, $userOne, $projectOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\Equals('id', $projectOne->getId()));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleInIdClause(): void
    {
        [$db, $userOne, $projectOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\In('id', [$projectOne->getId()]));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleInNotIdClause(): void
    {
        [$db, $userOne, $projectOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\NotIn('id', [2, 3]));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testCollectionInIdClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $projects = $db->getRepository(Project::class)->findAllCollection();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\In('id', $projects));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testArrayIteratorInIdClause(): void
    {
        [$db, $userOne, $projectOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\In('id', new \ArrayIterator([1])));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleEqualsNameClause(): void
    {
        [$db, $userOne, , $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\Equals('name', $projectTwo->getName()));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleEqualsNonExistingClause(): void
    {
        [$db, $userOne, , $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(
            new Clause\Condition\Equals('some_field', $projectTwo->getName()),
        );
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleGreatherThanClause(): void
    {
        [$db, $userOne, , $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\GreaterThan('id', 1));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleGreatherThanOrEqualsClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\GreaterThanOrEquals('id', 1));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleLessThanClause(): void
    {
        [$db, $userOne, $projectOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\LessThan('id', 2));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleLessThanOrEqualsClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\LessThanOrEquals('id', 2));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testMulitpleOrderByEqualsClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(
            new Clause\Multiple(
                new Clause\OrderBy\Ascending('id'),
                new Clause\Condition\GreaterThan('id', 0),
            ),
        );
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleNotEqualsClause(): void
    {
        [$db, $userOne, $projectOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\NotEquals('id', 2));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleIsNullClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\IsNull('published_at'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleIsNotNullClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Condition\IsNotNull('id'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testMulitpleNonMatchingEqualsClause(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Multiple(new Clause\Condition\Equals('id', 0)));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testMulitpleNonMatchingDoubleEqualsClause(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(
            new Clause\Multiple(
                new Clause\Condition\Equals('id', 1),
                new Clause\Condition\Equals('id', 2),
            ),
        );
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testMulitpleDoubleMatchingEqualsClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(
            new Clause\MultipleOr(
                new Clause\Condition\Equals('id', 1),
                new Clause\Condition\Equals('id', 2),
            ),
        );
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testEmptyMulitpleOrClause(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\MultipleOr());
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testMulitpleEmptyClause(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'projects' => [
                [
                    'id' => $projectOne->getId(),
                    'name' => $projectOne->getName(),
                ],
                [
                    'id' => $projectTwo->getId(),
                    'name' => $projectTwo->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Multiple());
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testPresentDates(): void
    {
        [$db, , $projectOne] = $this->createAndSetupEntities();

        $now = new \DateTimeImmutable();

        $expected = [
            'id' => $projectOne->getId(),
            'publishedAtDate' => null,
            'publishedAtDateTime' => null,
            'updatedAtDateTime' => $now->format(\DateTime::ATOM),
        ];

        $presenter = new Presenter($db);
        $result = $presenter->presentEntity(ProjectWithDatesPresenter::class, $projectOne);

        $this->assertEquals($expected, $result);

        $projectOne->setPublishedAt($now);

        $expected = [
            'id' => $projectOne->getId(),
            'publishedAtDate' => $now->format('Y-m-d'),
            'publishedAtDateTime' => $now->format(\DateTime::ATOM),
            'updatedAtDateTime' => $now->format(\DateTime::ATOM),
        ];

        $presenter = new Presenter($db);
        $result = $presenter->presentEntity(ProjectWithDatesPresenter::class, $projectOne);

        $this->assertEquals($expected, $result);
    }

    public function testPresentThrough(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'owners' => [
                // Should this be duplicated? Or should it be unique? There are
                // two projects with this user as its owner.. Hmm..
                [
                    'id' => $userOne->getId(),
                ],
            ],
            'noOwners' => [],
            'owner' => [
                'id' => $userOne->getId(),
            ],
            'noOwner' => null,
        ];

        $presenter = new Presenter($db);
        $result = $presenter->presentEntity(UserWithUserPresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testPresentEmpty(): void
    {
        [$db, , $projectOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $projectOne->getId(),
            'owner' => null,
            'projects' => [],
            'ownedProjects' => [],
            'someProject' => null,
            'someProjects' => [],
        ];

        $presenter = new Presenter($db);
        $result = $presenter->presentEntity(ProjectWithEmptyPresenter::class, $projectOne);

        $this->assertEquals($expected, $result);
    }

    public function testPresentProvideCollection(): void
    {
        [$db, $userOne, $projectOne, $projectTwo] = $this->createAndSetupEntities();

        $projectCollection = new Collection($db);
        $projectCollection->addEntity($projectOne);
        $projectCollection->addEntity($projectTwo);

        $profiler = $db->getProfiler();
        $currentNumQueries = $profiler->count();

        $db->presentCollection(ProjectWithOwnerPresenter::class, $projectCollection);

        // a single extra query is needed to fetch the owners of the projects
        $numQueries = $profiler->count() - $currentNumQueries;
        $this->assertEquals(1, $numQueries);

        $userCollection = new Collection($db);
        $userCollection->addEntity($userOne);

        $currentNumQueries = $profiler->count();

        $presenter = new Presenter($db);
        $presenter->provideCollection(User::class, $userCollection);
        $presenter->presentCollection(ProjectWithOwnerPresenter::class, $projectCollection);

        // no extra queries needed if the required entities are already
        // provided to the presenter
        $numQueries = $profiler->count() - $currentNumQueries;
        $this->assertEquals(0, $numQueries);
    }

    public function testSimpleFilterUnique(): void
    {
        $db = self::createDatabase();

        $user = $this->createUser($db, 'Name');

        $p1 = $this->createProject($db, $user, 'Same name');

        $expected = [
            'id' => $user->getId(),
            'projects' => [
                [
                    'id' => $p1->getId(),
                    'name' => $p1->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Filter\Unique('name'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $user);

        $this->assertEquals($expected, $result);
    }

    public function testSimpleFilterUniqueMissingField(): void
    {
        $db = self::createDatabase();

        $user = $this->createUser($db, 'Name');

        $this->createProject($db, $user, 'Same name');

        $expected = [
            'id' => $user->getId(),
            'projects' => [],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(new Clause\Filter\Unique('missing_field'));
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $user);

        $this->assertEquals($expected, $result);
    }

    public function testMultipleFilterUnique(): void
    {
        $db = self::createDatabase();

        $user = $this->createUser($db, 'Name');

        $p1 = $this->createProject($db, $user, 'Same name');
        $this->createProject($db, $user, 'Same name');
        $p3 = $this->createProject($db, $user, 'Other name');

        $expected = [
            'id' => $user->getId(),
            'projects' => [
                [
                    'id' => $p3->getId(),
                    'name' => $p3->getName(),
                ],
                [
                    'id' => $p1->getId(),
                    'name' => $p1->getName(),
                ],
            ],
        ];

        $presenter = new Presenter($db);
        $presenter->addDependency(
            new Clause\Multiple(
                new Clause\Filter\Unique('id'),
                new Clause\Filter\Unique('name'),
                new Clause\OrderBy\Descending('id'),
            ),
        );
        $result = $presenter->presentEntity(UserWithClausePresenter::class, $user);

        $this->assertEquals($expected, $result);
    }

    public function testInvalidPresenterKlass(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid presenter: ' . BrokenPresenter::class);

        /**
         * SAFEFY We want to check for broken arguments
         * @psalm-suppress InvalidArgument
         */
        $db->presentEntity(BrokenPresenter::class, $userOne);
    }

    public function testInvalidPresenterKlassWithoutKlass(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Missing entity klass for presenter: ' . BrokenWithoutEntityKlassPresenter::class,
        );

        $db->presentEntity(BrokenWithoutEntityKlassPresenter::class, $userOne);
    }

    public function testInvalidPresenterKlassNonPublicReceiveDependencies(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported dependency demand: method not public');

        $db->presentEntity(BrokenNonPublicReceiveDependenciesPresenter::class, $userOne);
    }

    public function testInvalidPresenterKlassVariadicParameters(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported dependency demand: no variadic parameters');

        $db->presentEntity(BrokenVariadicParametersPresenter::class, $userOne);
    }

    public function testInvalidPresenterKlassMissingType(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported dependency demand: missing valid type');

        $db->presentEntity(BrokenMissingTypePresenter::class, $userOne);
    }

    public function testPresenterKlassOptionalDependency(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'user' => null,
        ];

        $result = $db->presentEntity(UserOptionalDependencyPresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testInvalidPresenterKlassMissingDependency(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            sprintf('Unsupported dependency demand: "%s" not available', User::class),
        );

        $db->presentEntity(BrokenMissingDependencyPresenter::class, $userOne);
    }

    public function testInvalidPresenterKlassInfiniteLoop(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Presenter loop detected, this likely happens when a future marker keeps returning a future marker',
        );

        $db->presentEntity(BrokenInfiniteLoopPresenter::class, $userOne);
    }

    public function testPresenterDatabaseInjection(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $userOne->getId(),
            'dbClassName' => get_class($db),
        ];

        $result = $db->presentEntity(UserWithDatabasePresenter::class, $userOne);

        $this->assertEquals($expected, $result);
    }

    public function testPresenterEmptyResult(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $expected = null;

        $result = $db->presentEntity(UserEmptyResultPresenter::class, $userOne);

        $this->assertEquals($expected, $result);

        $presenter = new Presenter($db);
        $result = $presenter->presentEntity(UserEmptyResultPresenter::class, null);

        $this->assertEquals($expected, $result);

        $expected = [];

        $collection = new Collection($db);
        $collection->addEntity($userOne);

        $result = $db->presentCollection(UserEmptyResultPresenter::class, $collection);

        $this->assertEquals($expected, $result);
    }

    public function testPresenterManualMark(): void
    {
        [$db, $userOne] = $this->createAndSetupEntities();

        $expected = [
            'someUser' => [
                'id' => $userOne->getId(),
            ],
        ];

        $presenter = new Presenter($db);

        $presentation = [
            'someUser' => $presenter->mark(PlainUserPresenter::class, $userOne->getId()),
        ];

        $result = $presenter->processPresentation($presentation);

        $this->assertEquals($expected, $result);
    }

    public function testPresenterWithReceiveDependencies(): void
    {
        [$db, , $projectOne] = $this->createAndSetupEntities();

        $expected = [
            'id' => $projectOne->getId(),
            'status' => 'In progress',
        ];

        $statusFormatter = new StatusFormatter();

        $presenter = new Presenter($db);
        $presenter->addDependency($statusFormatter);
        $result = $presenter->presentEntity(
            ProjectWithReceiveDependenciesPresenter::class,
            $projectOne,
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Helper to create a user in a single line
     */
    private function createUser(Database $db, string $name): User
    {
        $user = new User();
        $user->setName($name);
        $db->save($user);

        return $user;
    }

    /**
     * Helper to create a project in a single line
     */
    private function createProject(Database $db, User $user, string $name): Project
    {
        $project = new Project();
        $project->setOwnerId($user);
        $project->setName($name);
        $db->save($project);

        return $project;
    }
}
