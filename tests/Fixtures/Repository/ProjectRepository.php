<?php

declare(strict_types=1);

namespace Tests\Fixtures\Repository;

use Tests\Fixtures\Entity\Project;

use Access\Collection;
use Access\Query\Select;
use Access\Repository;

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

    public function findAll(): Collection
    {
        $query = new Select(Project::class);
        $query->orderBy('id ASC');

        return $this->selectCollection($query);
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
}
