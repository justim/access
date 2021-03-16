<?php

declare(strict_types=1);

namespace Tests\Query;

use PHPUnit\Framework\TestCase;

use Access\Query\Update;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

class UpdateTest extends TestCase
{
    public function testQuery(): void
    {
        $query = new Update(User::class);
        $query->values([
            'name' => 'Dave',
        ]);
        $query->where([
            'id = ?' => 1,
        ]);

        $this->assertEquals(
            'UPDATE `users` SET `name` = :p0 WHERE (`users`.`deleted_at` IS NULL) AND (id = :w0)',
            $query->getSql(),
        );
        $this->assertEquals(['p0' => 'Dave', 'w0' => 1], $query->getValues());

        $query = new Update(Project::class);
        $query->values([
            'name' => 'Some project',
        ]);
        $query->where([
            'id = ?' => 1,
        ]);

        $this->assertEquals('UPDATE `projects` SET `name` = :p0 WHERE (id = :w0)', $query->getSql());
        $this->assertEquals(['p0' => 'Some project', 'w0' => 1], $query->getValues());
    }

    public function testQueryAlias(): void
    {
        $query = new Update(User::class, 'u');
        $query->values([
            'name' => 'Dave',
        ]);
        $query->where([
            'u.id = ?' => 1,
        ]);

        $this->assertEquals(
            'UPDATE `users` AS `u` SET `name` = :p0 WHERE (`u`.`deleted_at` IS NULL) AND (u.id = :w0)',
            $query->getSql(),
        );
        $this->assertEquals(['p0' => 'Dave', 'w0' => 1], $query->getValues());

        $query = new Update(Project::class, 'p');
        $query->values([
            'name' => 'Some project',
        ]);
        $query->where([
            'p.id = ?' => 1,
        ]);

        $this->assertEquals('UPDATE `projects` AS `p` SET `name` = :p0 WHERE (p.id = :w0)', $query->getSql());
        $this->assertEquals(['p0' => 'Some project', 'w0' => 1], $query->getValues());
    }

    public function testQueryJoin(): void
    {
        $query = new Update(User::class, 'u');
        $query->innerJoin(Project::class, 'p', 'p.owner_id = u.id');
        $query->values([
            'name' => 'Dave',
        ]);

        $this->assertEquals(
            'UPDATE `users` AS `u` INNER JOIN `projects` AS `p` ON (p.owner_id = u.id) SET `name` = :p0 WHERE (`u`.`deleted_at` IS NULL)',
            $query->getSql(),
        );
        $this->assertEquals(['p0' => 'Dave'], $query->getValues());

        $query = new Update(Project::class, 'p');
        $query->innerJoin(User::class, 'u', 'p.owner_id = u.id');
        $query->values([
            'name' => 'Some project',
        ]);

        $this->assertEquals(
            'UPDATE `projects` AS `p` INNER JOIN `users` AS `u` ON ((`u`.`deleted_at` IS NULL) AND (p.owner_id = u.id)) SET `name` = :p0',
            $query->getSql(),
        );
        $this->assertEquals(['p0' => 'Some project'], $query->getValues());
    }
}
