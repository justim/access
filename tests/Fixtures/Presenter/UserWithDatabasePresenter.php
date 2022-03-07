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

use Access\Database;
use Access\Entity;
use Access\Presenter\EntityPresenter;
use Tests\Fixtures\Entity\User;

/**
 * User with database presenter
 * @template-extends EntityPresenter<User>
 */
class UserWithDatabasePresenter extends EntityPresenter
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public static function getEntityKlass(): string
    {
        return User::class;
    }

    public function fromEntity(Entity $user): ?array
    {
        return [
            'id' => $user->getId(),
            'dbClassName' => get_class($this->db),
        ];
    }
}
