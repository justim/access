<?php

declare(strict_types=1);

namespace Tests\Query;

use PHPUnit\Framework\TestCase;

use Access\Query\Delete;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

class DeleteTest extends TestCase
{
    public function testQuery(): void
    {
        $query = new Delete(User::class);
        $query->where([
            'id = ?' => 1,
        ]);

        $this->assertEquals('DELETE FROM `users` WHERE (id = :w0)', $query->getSql());
        $this->assertEquals(['w0' => 1], $query->getValues());
    }

    public function testQueryAlias(): void
    {
        $query = new Delete(User::class, 'u');
        $query->where([
            'u.id = ?' => 1,
        ]);

        $this->assertEquals('DELETE `u` FROM `users` AS `u` WHERE (u.id = :w0)', $query->getSql());
        $this->assertEquals(['w0' => 1], $query->getValues());
    }

    public function testQueryJoin(): void
    {
        $query = new Delete(User::class, 'u');
        $query->innerJoin(Project::class, 'p', 'p.owner_id = u.id');

        $this->assertEquals(
            'DELETE `u` FROM `users` AS `u` INNER JOIN `projects` AS `p` ON (p.owner_id = u.id)',
            $query->getSql()
        );
        $this->assertEquals([], $query->getValues());
    }
}
