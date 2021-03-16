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

namespace Tests\Fixtures\Presenter;

use Access\Entity;
use Access\Presenter\EntityPresenter;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\StatusFormatter;

/**
 * Project with `receiveDepencies` presenter
 */
class ProjectWithReceiveDependenciesPresenter extends EntityPresenter
{
    private ?StatusFormatter $statusFormatter = null;

    public function receiveDependencies(StatusFormatter $statusFormatter)
    {
        $this->statusFormatter = $statusFormatter;
    }

    public static function getEntityKlass(): string
    {
        return Project::class;
    }

    /**
     * @param Project $project
     */
    public function fromEntity(Entity $project): ?array
    {
        return [
            'id' => $project->getId(),
            'status' => $this->statusFormatter->format($project->getStatus()),
        ];
    }
}
