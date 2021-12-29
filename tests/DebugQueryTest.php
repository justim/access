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

namespace Tests;

use Access\Clause;
use Access\DebugQuery;
use Access\Query;

use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

class DebugQueryTest extends AbstractBaseTestCase
{
    public function testSimpleNull(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->where('u.id = ?', null);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.id IS NULL',
            $runnableSql,
        );

        $query = new Query\Select(Project::class, 'p');
        $query->where('p.id = ?', null);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals('SELECT `p`.* FROM `projects` AS `p` WHERE p.id IS NULL', $runnableSql);
    }

    public function testSimpleNotNull(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->where('u.id != ?', null);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.id IS NOT NULL',
            $runnableSql,
        );

        $query = new Query\Select(Project::class, 'p');
        $query->where('p.id !=?', null);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` WHERE p.id IS NOT NULL',
            $runnableSql,
        );
    }

    public function testSimpleInt(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->where('u.id = ?', 1);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.id = 1',
            $runnableSql,
        );

        $query = new Query\Select(Project::class, 'p');
        $query->where('p.id = ?', 1);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals('SELECT `p`.* FROM `projects` AS `p` WHERE p.id = 1', $runnableSql);
    }

    public function testSimpleString(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->where('u.name = ?', 'Dave');

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.name = "Dave"',
            $runnableSql,
        );

        $query = new Query\Select(Project::class, 'p');
        $query->where('p.name = ?', 'Dave');

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` WHERE p.name = "Dave"',
            $runnableSql,
        );
    }

    public function testSimpleBool(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->where('u.active = ?', true);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.active = 1',
            $runnableSql,
        );

        $query = new Query\Select(Project::class, 'p');
        $query->where('p.active = ?', true);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals('SELECT `p`.* FROM `projects` AS `p` WHERE p.active = 1', $runnableSql);
    }

    public function testSimpleDatetime(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->where('u.created_at < ?', new \DateTime('2000-01-01 00:00:00'));

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.created_at < "2000-01-01 00:00:00"',
            $runnableSql,
        );

        $query = new Query\Select(Project::class, 'p');
        $query->where('p.created_at < ?', new \DateTime('2000-01-01 00:00:00'));

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` WHERE p.created_at < "2000-01-01 00:00:00"',
            $runnableSql,
        );
    }

    public function testMultipleInts(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->where('u.id IN (?)', [1, 2]);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.id IN (1, 2)',
            $runnableSql,
        );

        $query = new Query\Select(Project::class, 'p');
        $query->where('p.id IN (?)', [1, 2]);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` WHERE p.id IN (1, 2)',
            $runnableSql,
        );
    }

    public function testEscapedString(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->where('u.name = ?', 'Da"ve');

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.name = "Da\"ve"',
            $runnableSql,
        );

        $query = new Query\Select(Project::class, 'p');
        $query->where('p.name = ?', 'Da"ve');

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` WHERE p.name = "Da\"ve"',
            $runnableSql,
        );
    }

    public function testBinaryString(): void
    {
        // see DoctrineBundle escape function tests
        $binaryString = pack('H*', '9d40b8c1417f42d099af4782ec4b20b6');

        $query = new Query\Select(User::class, 'u');
        $query->where('u.name = ?', $binaryString);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND u.name = 0x9D40B8C1417F42D099AF4782EC4B20B6',
            $runnableSql,
        );
    }

    public function testQueryWhereOr(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->whereOr([
            'u.id = ?' => 1,
            'u.name = ?' => 'Dave',
        ]);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` WHERE `u`.`deleted_at` IS NULL AND (u.id = 1 OR u.name = "Dave")',
            $runnableSql,
        );

        $query = new Query\Select(Project::class, 'p');
        $query->whereOr([
            'p.id = ?' => 1,
            'p.name = ?' => 'Dave',
        ]);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `p`.* FROM `projects` AS `p` WHERE (p.id = 1 OR p.name = "Dave")',
            $runnableSql,
        );
    }

    public function testSimpleNullInsert(): void
    {
        $query = new Query\Insert(User::class, 'u');
        $query->values(['name' => null]);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals('INSERT INTO `users` (name) VALUES (NULL)', $runnableSql);
    }

    public function testSimpleEqualsConditionClause(): void
    {
        $query = new Query\Select(User::class);
        $query->where(new Clause\Condition\Equals('name', 'Dave'));

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND `name` = "Dave"',
            $runnableSql,
        );
    }

    public function testSimpleNotEqualsConditionClause(): void
    {
        $query = new Query\Select(User::class);
        $query->where(new Clause\Condition\NotEquals('name', 'Dave'));

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND `name` != "Dave"',
            $runnableSql,
        );
    }

    public function testSimpleInConditionClause(): void
    {
        $query = new Query\Select(User::class);
        $query->where(new Clause\Condition\In('name', ['Dave']));

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND `name` IN ("Dave")',
            $runnableSql,
        );
    }

    public function testSimpleNotInConditionClause(): void
    {
        $query = new Query\Select(User::class);
        $query->where(new Clause\Condition\NotIn('name', ['Dave']));

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND `name` NOT IN ("Dave")',
            $runnableSql,
        );
    }

    public function testSimpleMultipleConditionClause(): void
    {
        $query = new Query\Select(User::class);
        $query->where(
            new Clause\Multiple(
                new Clause\Condition\Equals('name', 'Dave'),
                new Clause\Condition\GreaterThan('id', 0),
            ),
        );

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND (`name` = "Dave" AND `id` > 0)',
            $runnableSql,
        );
    }

    public function testSimpleEmptyMultipleConditionClause(): void
    {
        $query = new Query\Select(User::class);
        $query->where(new Clause\Multiple());

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND 1 = 2',
            $runnableSql,
        );
    }

    public function testMixedMultipleConditionClause(): void
    {
        $query = new Query\Select(User::class);
        $query->where([
            'name = ?' => 'Dave',
            new Clause\Condition\LessThan('id', 0),
        ]);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND (name = "Dave" AND `id` < 0)',
            $runnableSql,
        );
    }

    public function testMixedMultipleOrConditionClause(): void
    {
        $query = new Query\Select(User::class);
        $query->where([
            'name = ?' => 'Dave',
            new Clause\MultipleOr(
                new Clause\Condition\GreaterThanOrEquals('id', 5),
                new Clause\Condition\LessThanOrEquals('id', 2),
            ),
        ]);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `users`.* FROM `users` WHERE `users`.`deleted_at` IS NULL AND (name = "Dave" AND (`id` >= 5 OR `id` <= 2))',
            $runnableSql,
        );
    }

    public function testDroppedQuery(): void
    {
        $query = new Query\Update(User::class);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        // nothing to update, query is dropped
        $this->assertNull($runnableSql);
    }

    public function testSimpleUpdateQuery(): void
    {
        $query = new Query\Update(User::class);
        $query->values(['name' => 'Dave']);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'UPDATE `users` SET `name` = "Dave" WHERE `users`.`deleted_at` IS NULL',
            $runnableSql,
        );
    }

    public function testUpdateQuery(): void
    {
        $query = new Query\Update(User::class);
        $query->values(['name' => 'Dave']);
        $query->where('id = ?', 1);

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'UPDATE `users` SET `name` = "Dave" WHERE `users`.`deleted_at` IS NULL AND id = 1',
            $runnableSql,
        );
    }

    public function testJoinQuery(): void
    {
        $query = new Query\Select(User::class, 'u');
        $query->innerJoin(Project::class, 'p', new Clause\Condition\Relation('p.owner_id', 'u.id'));

        $debug = new DebugQuery($query);

        $runnableSql = $debug->toRunnableQuery();

        $this->assertEquals(
            'SELECT `u`.* FROM `users` AS `u` INNER JOIN `projects` AS `p` ON `p`.`owner_id` = `u`.`id` WHERE `u`.`deleted_at` IS NULL',
            $runnableSql,
        );
    }
}
