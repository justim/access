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

use Access\Clause\ClauseInterface;
use Access\Entity;
use Access\Presenter\EntityPresenter;
use Tests\Fixtures\Entity\User;

/**
 * User presenter with an optional injectable clause
 * @template-extends EntityPresenter<User>
 */
class UserWithClausePresenter extends EntityPresenter
{
    private ?ClauseInterface $clause = null;

    public function __construct(ClauseInterface $clause = null)
    {
        $this->clause = $clause;
    }

    public static function getEntityKlass(): string
    {
        return User::class;
    }

    /**
     * @param User $user
     * @return array<string, mixed>|null Array representation
     */
    public function fromEntity(Entity $user): ?array
    {
        return [
            'id' => $user->getId(),
            'projects' => $this->presentMultipleInversedRefs(
                SimpleProjectPresenter::class,
                'owner_id',
                $user->getId(),
                $this->clause,
            ),
        ];
    }
}
