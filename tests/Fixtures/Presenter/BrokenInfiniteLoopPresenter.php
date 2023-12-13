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
 * Broken infinite loop presenter
 *
 * @template-extends EntityPresenter<User>
 */
class BrokenInfiniteLoopPresenter extends EntityPresenter
{
    public static function getEntityKlass(): string
    {
        return User::class;
    }

    public function fromEntity(Entity $entity): ?array
    {
        return [
            'id' => $entity->getId(),
            'user' => $this->present(BrokenInfiniteLoopPresenter::class, $entity->getId()),
        ];
    }
}
