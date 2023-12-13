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
 * Broken variadic parameters presenter
 *
 * @template-extends EntityPresenter<User>
 */
class BrokenVariadicParametersPresenter extends EntityPresenter
{
    private array $users;

    public function __construct(User ...$users)
    {
        $this->users = $users;
    }

    public static function getEntityKlass(): string
    {
        return User::class;
    }

    public function fromEntity(Entity $entity): ?array
    {
        return [
            'id' => $entity->getId(),
            'users' => $this->users,
        ];
    }
}
