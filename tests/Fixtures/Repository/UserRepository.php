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

use Tests\Fixtures\Entity\User;

use Access\Collection;
use Access\Repository;

/**
 * @template-extends Repository<User>
 */
class UserRepository extends Repository
{
    public function findNothing(): Collection
    {
        return $this->createEmptyCollection();
    }
}
