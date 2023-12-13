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
 * Broken missing type presenter
 *
 * @template-extends EntityPresenter<User>
 */
class BrokenMissingTypePresenter extends EntityPresenter
{
    /**
     * SAFEFY Type is missing for testing
     * @psalm-suppress MissingPropertyType
     */
    private $user;

    /**
     * SAFEFY Type is missing for testing
     * @psalm-suppress MissingParamType
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    public static function getEntityKlass(): string
    {
        return User::class;
    }

    public function fromEntity(Entity $entity): ?array
    {
        return [
            'id' => $entity->getId(),
            'user' => $this->user instanceof User ? $this->user->getId() : null,
        ];
    }
}
