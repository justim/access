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
use Tests\Fixtures\Entity\User;

/**
 * Broken missing dependency presenter
 */
class BrokenMissingDependencyPresenter extends EntityPresenter
{
    private ?User $user = null;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public static function getEntityKlass(): string
    {
        return User::class;
    }

    public function fromEntity(Entity $user): ?array
    {
        return [
            'id' => $user->getId(),
            'dependencyUserId' => $this->user?->getId(),
        ];
    }
}
