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
use Access\Query;
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

    public function findDuplicates(): Collection
    {
        $queryOne = new Query\Select(User::class, 'u1', [
            // make sure the field names are unique, so union will include all records
            'one' => '1',
        ]);

        $queryTwo = new Query\Select(User::class, 'u2', [
            'two' => '2',
        ]);

        $query = new Query\Union($queryOne, $queryTwo);

        return $this->selectCollection($query);
    }
}
