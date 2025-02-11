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
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\StatusFormatter;

/**
 * Project presenter
 * @template-extends EntityPresenter<Project>
 */
class ProjectPresenter extends EntityPresenter
{
    private StatusFormatter $statusFormatter;

    public function __construct(StatusFormatter $statusFormatter)
    {
        $this->statusFormatter = $statusFormatter;
    }

    public static function getEntityKlass(): string
    {
        return Project::class;
    }

    /**
     * @param Project $project
     * @return array<string, mixed>|null Array representation
     */
    public function fromEntity(Entity $project): ?array
    {
        return [
            'id' => $project->getId(),
            'status' => $this->statusFormatter->format($project->getStatus()),
            'owner' => $this->present(UserPresenter::class, $project->getOwnerId()),
            'ownerFuture' => $this->presentFuture(
                User::class,
                $project->getOwnerId(),
                fn(User $user) => $this->present(UserPresenter::class, $user),
            ),
        ];
    }
}
