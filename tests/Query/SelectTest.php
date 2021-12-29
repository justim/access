<?php

declare(strict_types=1);

namespace Tests\Query;

use Access\Clause\Condition\Relation;
use PHPUnit\Framework\TestCase;

use Access\Exception;
use Access\Query\Select;

use Tests\Fixtures\Entity\MissingTableEntity;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

class SelectTest extends TestCase
{
    public function testMissingTableName(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No table given for query');

        new Select(MissingTableEntity::class);
    }

    public function testQuery(): void
    {
        $query = new Select(User::class);

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL',
            $query->getSql(),
        );

        $query = new Select(Project::class);

        $this->assertEquals('SELECT `projects`.* FROM `projects`', $query->getSql());
    }

    public function testQueryWithAlias(): void
    {
        $query = new Select(User::class, 'u');

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL',
            $query->getSql(),
        );

        $query = new Select(Project::class, 'p');

        $this->assertEquals('SELECT `p`.* FROM `projects` AS `p`', $query->getSql());
    }

    public function testQueryWhere(): void
    {
        $name = 'John';

        $query = new Select(User::class);
        $query->where([
            'name = ?' => $name,
        ]);

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND name = :w0',
            $query->getSql(),
        );
        $this->assertEquals(['w0' => $name], $query->getValues());

        $query = new Select(User::class);
        $query->where('name IS NOT NULL');

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND name IS NOT NULL',
            $query->getSql(),
        );
        $this->assertEquals([], $query->getValues());

        $query = new Select(User::class);
        $query->where('name = ?', $name);

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND name = :w0',
            $query->getSql(),
        );
        $this->assertEquals(['w0' => $name], $query->getValues());
    }

    public function testQueryJoin(): void
    {
        $query = new Select(Project::class, 'p');
        $query->innerJoin(User::class, 'u', 'p.owner_id = u.id');

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` INNER JOIN `users` AS `u` ON `u`.`deleted_at` IS NULL AND p.owner_id = u.id',
            $query->getSql(),
        );

        $query = new Select(Project::class, 'p');
        $query->leftJoin(User::class, 'u', 'p.owner_id = u.id');

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` LEFT JOIN `users` AS `u` ON `u`.`deleted_at` IS NULL AND p.owner_id = u.id',
            $query->getSql(),
        );
    }

    public function testQueryJoinRelation(): void
    {
        $query = new Select(Project::class, 'p');
        $query->innerJoin(User::class, 'u', new Relation('p.owner_id', 'u.id'));

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` INNER JOIN `users` AS `u` ON `u`.`deleted_at` IS NULL AND `p`.`owner_id` = `u`.`id`',
            $query->getSql(),
        );
    }

    public function testQueryGroupBy(): void
    {
        $query = new Select(Project::class, 'p');
        $query->groupBy('p.id');

        $this->assertEquals('SELECT `p`.* FROM `projects` AS `p` GROUP BY p.id', $query->getSql());
    }

    public function testQueryLimit(): void
    {
        $query = new Select(Project::class, 'p');
        $query->limit(10);

        $this->assertEquals('SELECT `p`.* FROM `projects` AS `p` LIMIT 10', $query->getSql());
    }

    public function testQueryHaving(): void
    {
        $query = new Select(User::class, 'u', [
            'total_projects' => 'COUNT(p.id)',
        ]);
        $query->leftJoin(Project::class, 'p', 'p.owner_id = u.id');

        $query->groupBy('u.id');
        $query->having([
            'total_projects > ?' => 1,
        ]);

        $query->having('u.name IS NOT NULL');

        $this->assertEquals(
            'SELECT `u`.*, COUNT(p.id) AS `total_projects` FROM `users` AS `u` ' .
                'LEFT JOIN `projects` AS `p` ON p.owner_id = u.id ' .
                'WHERE `u`.`deleted_at` IS NULL ' .
                'GROUP BY u.id ' .
                'HAVING total_projects > :h0 AND u.name IS NOT NULL',
            $query->getSql(),
        );

        $this->assertEquals(['h0' => 1], $query->getValues());
    }

    public function testQueryOrder(): void
    {
        $query = new Select(Project::class, 'p');
        $query->orderBy('p.name ASC');

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` ORDER BY p.name ASC',
            $query->getSql(),
        );
    }

    public function testSubquery(): void
    {
        $subQuery = new Select(Project::class, 'p');
        $subQuery->select('COUNT(p.id)');
        $subQuery->where('p.user_id = u.id');
        $subQuery->where('p.status = ?', 'IN_PROGRESS');

        $query = new Select(User::class, 'u', [
            'total_projects' => $subQuery,
        ]);

        $query->innerJoin(Project::class, 'pp', ['pp.user_id = u.id', 'pp.id = ?' => 1]);

        $query->where('u.first_name = ?', 'Dave');

        $this->assertEquals(
            'SELECT `u`.*, (SELECT COUNT(p.id) FROM `projects` AS `p` WHERE ' .
                'p.user_id = u.id AND p.status = :s0w0) AS `total_projects` FROM `users` AS `u` ' .
                'INNER JOIN `projects` AS `pp` ON (pp.user_id = u.id AND pp.id = :j0j0) ' .
                'WHERE `u`.`deleted_at` IS NULL AND u.first_name = :w0',
            $query->getSql(),
        );

        $this->assertEquals(
            [
                's0w0' => 'IN_PROGRESS',
                'w0' => 'Dave',
                'j0j0' => 1,
            ],
            $query->getValues(),
        );
    }

    public function testSubqueryWhere(): void
    {
        $subQuery = new Select(Project::class, 'p');
        $subQuery->select('p.user_id');
        $subQuery->where('p.status = ?', 'IN_PROGRESS');
        $subQuery->limit(1);

        $query = new Select(User::class, 'u');
        $query->where('u.id = ?', $subQuery);

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.id = (SELECT p.user_id FROM `projects` AS `p` WHERE p.status = :z0w0 LIMIT 1)',
            $query->getSql(),
        );

        $this->assertEquals(
            [
                'z0w0' => 'IN_PROGRESS',
            ],
            $query->getValues(),
        );
    }

    public function testSubqueryDoubleWhere(): void
    {
        $subQueryOne = new Select(Project::class, 'p1');
        $subQueryOne->select('p1.user_id');
        $subQueryOne->where('p1.status = ?', 'IN_PROGRESS');
        $subQueryOne->limit(1);

        $subQueryTwo = new Select(Project::class, 'p2');
        $subQueryTwo->select('p2.user_id');
        $subQueryTwo->where('p2.status != ?', 'FINISHED');
        $subQueryTwo->where('p2.name = ?', 'Access');
        $subQueryTwo->limit(1);

        $query = new Select(User::class, 'u');
        $query->whereOr([
            'u.id = ?' => $subQueryOne,
            'u.external_id = ?' => $subQueryTwo,
        ]);

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND ' .
                '(u.id = (SELECT p1.user_id FROM `projects` AS `p1` WHERE p1.status = :z0w0 LIMIT 1) ' .
                'OR u.external_id = (SELECT p2.user_id FROM `projects` AS `p2` WHERE p2.status != :z1w0 AND p2.name = :z1w1 LIMIT 1))',
            $query->getSql(),
        );

        $this->assertEquals(
            [
                'z0w0' => 'IN_PROGRESS',
                'z1w0' => 'FINISHED',
                'z1w1' => 'Access',
            ],
            $query->getValues(),
        );
    }

    public function testSubqueryMixed(): void
    {
        $subQueryTotal = new Select(Project::class, 'p1');
        $subQueryTotal->select('COUNT(p1.id)');
        $subQueryTotal->where('p1.user_id = u.id');
        $subQueryTotal->where('p1.status = ?', 'IN_PROGRESS');

        $subQueryInProgress = new Select(Project::class, 'p2');
        $subQueryInProgress->select('p2.user_id');
        $subQueryInProgress->where('p2.status = ?', 'IN_PROGRESS');
        $subQueryInProgress->limit(1);

        $query = new Select(User::class, 'u', [
            'total_projects' => $subQueryTotal,
        ]);

        $query->where('u.id = ?', $subQueryInProgress);

        $this->assertEquals(
            'SELECT `u`.*, (SELECT COUNT(p1.id) FROM `projects` AS `p1` WHERE ' .
                'p1.user_id = u.id AND p1.status = :s0w0) AS `total_projects` FROM `users` AS `u` ' .
                'WHERE `u`.`deleted_at` IS NULL AND u.id = (SELECT p2.user_id FROM `projects` AS `p2` WHERE p2.status = :z0w0 LIMIT 1)',
            $query->getSql(),
        );

        $this->assertEquals(
            [
                's0w0' => 'IN_PROGRESS',
                'z0w0' => 'IN_PROGRESS',
            ],
            $query->getValues(),
        );
    }

    public function testSubqueryMultipleRows(): void
    {
        $subQueryInProgress = new Select(Project::class, 'p1');
        $subQueryInProgress->select('p1.user_id');
        $subQueryInProgress->where('p1.status = ?', 'IN_PROGRESS');

        $query = new Select(User::class, 'u');

        $query->where('u.id IN (?)', $subQueryInProgress);

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` ' .
                'WHERE `u`.`deleted_at` IS NULL AND u.id IN (SELECT p1.user_id FROM `projects` AS `p1` WHERE p1.status = :z0w0)',
            $query->getSql(),
        );

        $this->assertEquals(
            [
                'z0w0' => 'IN_PROGRESS',
            ],
            $query->getValues(),
        );
    }

    public function testMultipleSimilarWheres(): void
    {
        $query = new Select(Project::class, 'p');
        $query->where('p.title LIKE ?', '%1%');
        $query->where('p.title LIKE ?', '%2%');

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` WHERE p.title LIKE :w0 AND p.title LIKE :w1',
            $query->getSql(),
        );
        $this->assertEquals(
            [
                'w0' => '%1%',
                'w1' => '%2%',
            ],
            $query->getValues(),
        );
    }

    public function testNullValue(): void
    {
        $query = new Select(Project::class, 'p');
        $query->where('p.updated_at = ?', null);

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` WHERE p.updated_at IS NULL',
            $query->getSql(),
        );

        $this->assertEquals([], $query->getValues());
    }

    public function testInvalidWherConditionOne(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Values should be in condition array');

        $query = new Select(Project::class, 'p');
        $query->where([], 'bla');
    }

    public function testRawWithoutValue(): void
    {
        $query = new Select(Project::class, 'p');
        $query->where('p.id = p.id');

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` WHERE p.id = p.id',
            $query->getSql(),
        );

        $this->assertEquals([], $query->getValues());
    }
}
