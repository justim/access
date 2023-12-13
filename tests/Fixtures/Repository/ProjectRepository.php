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

namespace Tests\Fixtures\Repository;

use Tests\Fixtures\Entity\Project;

use Access\Collection;
use Access\EntityProvider\VirtualArrayEntity;
use Access\EntityProvider\VirtualArrayEntityProvider;
use Access\EntityProvider\VirtualEntity;
use Access\EntityProvider\VirtualEntityProvider;
use Access\Query\Select;
use Access\Query\Update;
use Access\Repository;
use Tests\Fixtures\Entity\User;

/**
 * @template-extends Repository<Project>
 */
class ProjectRepository extends Repository
{
    public function findInProgress(): \Generator
    {
        $query = new Select(Project::class, 'p');
        $query->where([
            'p.status = ?' => 'IN_PROGRESS',
        ]);

        return $this->selectBatched($query);
    }

    public function findNothing(): Collection
    {
        return $this->createEmptyCollection();
    }

    public function findByName(string $name): ?Project
    {
        $query = new Select(Project::class);
        $query->where([
            'name = ?' => $name,
        ]);

        /** @var Project|null $project */
        $project = $this->selectOne($query);
        return $project;
    }

    public function setNameWithDirectQuery(int $id, string $name): void
    {
        $query = new Update(Project::class);
        $query->where('id = ?', $id);
        $query->values([
            'name' => $name,
        ]);

        $this->query($query);
    }

    /**
     * SAFETY Return types are not known, they are stored in an array config
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function findTotalCount(): int
    {
        $query = new Select(Project::class, 'p', [
            'total' => 'COUNT(*)',
        ]);

        return $this->selectOneVirtualField($query, 'total', 'int');
    }

    /**
     * SAFETY Return types are not known, they are stored in an array config
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function findTotalCountAdded(): int
    {
        $query = new Select(Project::class, 'p');

        $query->addVirtualField('total', 'COUNT(*)');

        return $this->selectOneVirtualField($query, 'total', 'int');
    }

    /**
     * SAFETY Return types are not known, they are stored in an array config
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function findTotalCountReplaced(): int
    {
        $query = new Select(Project::class, 'p', [
            'total' => '1',
        ]);

        $query->addVirtualField('total', 'COUNT(*)');

        return $this->selectOneVirtualField($query, 'total', 'int');
    }

    public function findWithUserName(): Collection
    {
        $query = new Select(Project::class, 'p', [
            'user_name' => 'u.name',
            'user_id' => 'u.id',
        ]);

        $query->innerJoin(User::class, 'u', ['p.owner_id = u.id']);

        return $this->selectCollection($query);
    }

    public function findVirtualUserNames(): \Generator
    {
        $query = new Select(Project::class, 'p', [
            'user_name' => 'u.name',
        ]);

        $query->innerJoin(User::class, 'u', ['p.owner_id = u.id']);

        /**
         * Extending the template does not seems to work for anonymous classes
         *
         * @psalm-suppress MissingTemplateParam
         */
        return $this->selectWithEntityProvider(
            $query,
            new class extends VirtualEntityProvider {
                public function create(): VirtualEntity
                {
                    return new class ([
                        'user_name' => [],
                        'user_id' => [
                            'type' => 'int',
                        ],
                    ]) extends VirtualEntity {
                        /**
                         * SAFETY Return types are not known, they are stored in an array config
                         * @psalm-suppress MixedReturnStatement
                         * @psalm-suppress MixedInferredReturnType
                         */
                        public function getUserName(): string
                        {
                            return $this->get('user_name');
                        }

                        /**
                         * SAFETY Return types are not known, they are stored in an array config
                         * @psalm-suppress MixedReturnStatement
                         * @psalm-suppress MixedInferredReturnType
                         */
                        public function getUserId(): int
                        {
                            return $this->get('user_id');
                        }
                    };
                }
            },
        );
    }

    /**
     * @psalm-return Collection<VirtualArrayEntity>
     */
    public function findVirtualArrayUserNames(): Collection
    {
        $query = new Select(Project::class, 'p', [
            'user_name' => 'u.name',
            'user_id' => 'u.id',
            'user_created_at' => 'u.created_at',
        ]);

        $query->innerJoin(User::class, 'u', ['p.owner_id = u.id']);
        $query->limit(1);

        /**
         * This should work, the `VirtualArrayEntityProvider` provider has the right template extends
         *
         * @psalm-suppress InvalidArgument
         * @var Collection<VirtualArrayEntity>
         */
        return $this->selectWithEntityProviderCollection(
            $query,
            new VirtualArrayEntityProvider([
                'user_name' => [],
                'user_id' => [
                    'type' => 'int',
                ],
                'user_created_at' => [
                    'type' => 'datetime',
                ],
            ]),
        );
    }

    public function findVirtualArrayUserNamesSingleField(): Collection
    {
        $query = new Select(Project::class, 'p');

        $query->select('u.name AS user_name');

        $query->innerJoin(User::class, 'u', ['p.owner_id = u.id']);
        $query->limit(1);

        /**
         * This should work, the `VirtualArrayEntityProvider` provider has the right template extends
         *
         * @psalm-suppress InvalidArgument
         */
        return $this->selectWithEntityProviderCollection(
            $query,
            new VirtualArrayEntityProvider([
                'user_name' => [],
            ]),
        );
    }

    public function brokenFindVirtualEntity(): Collection
    {
        $query = new Select(Project::class, 'p', [
            'user_name' => 'u.name',
        ]);

        $query->innerJoin(User::class, 'u', ['p.owner_id = u.id']);

        /**
         * SAFEFY This is a broken example, the virtual entity is not created
         * @psalm-suppress MissingTemplateParam
         */
        return $this->selectWithEntityProviderCollection(
            $query,
            new class extends VirtualEntityProvider {},
        );
    }

    public function findBatchedAll(): \Generator
    {
        $query = new Select(Project::class, 'p');

        return $this->selectBatched($query, 1);
    }
}
